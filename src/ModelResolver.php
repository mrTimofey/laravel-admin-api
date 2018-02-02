<?php

namespace MrTimofey\LaravelAdminApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MrTimofey\LaravelAdminApi\Contracts\HasAdminHandler;
use MrTimofey\LaravelAdminApi\Contracts\ModelResolver as Contract;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Resolves model instances.
 * @see Contract
 */
class ModelResolver implements Contract
{
    /**
     * config('admin_api.models')
     * @var array
     */
    protected $classes;

    /**
     * @var Request
     */
    protected $req;

    /**
     * Actions to check permissions
     * @var array
     */
    protected static $actions = [
        'index',
        'item',
        'create',
        'simpleCreate',
        'update',
        'destroy'
    ];

    public function __construct(array $classes, ?Request $req = null)
    {
        $this->classes = $classes;
        $this->req = $req ?? request();
    }

    /**
     * @inheritdoc
     */
    public function resolveModel(string $name): ?Model
    {
        // explicit
        if (isset($this->classes[$name])) {
            return new $this->classes[$name];
        }

        // implicit
        foreach ($this->classes as $k => $className) {
            if (!is_numeric($k)) {
                continue;
            }
            /** @var Model $model */
            $model = new $className;
            if ($model->getTable() === str_replace('-', '_', $name)) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function resolveHandler(string $name, Model $instance): ModelHandler
    {
        return $instance instanceof HasAdminHandler ?
            $instance->getAdminHandler($name, $this->req) :
            new ModelHandler($instance, $name, $this->req);
    }

    /**
     * Converts string keyed array to a regular array, puts key to a 'name' field of each element.
     * @param array|null $source
     * @return array|null
     */
    protected function convertToArray(?array $source): ?array
    {
        if (!$source) {
            return null;
        }
        $res = [];
        foreach ($source as $fieldName => $fieldConfig) {
            $config = $fieldConfig;
            $config['name'] = $fieldName;
            $res[] = $config;
        }
        return $res;
    }

    /**
     * @inheritdoc
     */
    public function getMeta(): array
    {
        $ar = [];
        foreach ($this->classes as $k => $class) {
            /** @var Model $model */
            $model = new $class;

            // default url chunk is same as table name with underscores replaced by dashes
            $name = is_numeric($k) ? str_replace('_', '-', $model->getTable()) : $k;
            $handler = $this->resolveHandler($name, $model);

            $ar[$name] = [
                'primary' => $model->getKeyName(),
                // map 'actionName' => true|false (action permitted or not)
                'permissions' => array_combine(
                    static::$actions,
                    array_map(function ($action) use ($handler) {
                        try {
                            $handler->authorize($action);
                            return true;
                        } catch (AccessDeniedHttpException $e) {
                            return false;
                        }
                    }, static::$actions)
                ),
                // convert string keyed arrays to regular arrays for JavaScript environment
                'filter_fields' => $this->convertToArray($handler->getFilterFields()),
                'index_fields' => $this->convertToArray($handler->getIndexFields()),
                'item_fields' => $this->convertToArray($handler->getItemFields()),
                'searchable' => $handler->isSearchable(),
                'title' => $handler->getTitle(),
                'item_title' => $handler->getItemTitle(),
                'create_title' => $handler->getCreateTitle()
            ];
        }

        return $ar;
    }
}
