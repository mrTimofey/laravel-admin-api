<?php

namespace App\Admin;

use App\Admin\Contracts\HasAdminHandler;
use App\Admin\Contracts\ModelResolver as Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ModelResolver implements Contract
{
    /**
     * @var array
     */
    protected $classes;

    /**
     * @var Request
     */
    protected $req;

    /**
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

    public function resolveHandler(string $name, Model $instance): ModelHandler
    {
        return $instance instanceof HasAdminHandler ?
            $instance->getAdminHandler($name, $this->req) :
            new ModelHandler($instance, $name, $this->req);
    }

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
            $name = is_numeric($k) ? str_replace('_', '-', $model->getTable()) : $k;
            $handler = $this->resolveHandler($name, $model);

            $ar[$name] = [
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
