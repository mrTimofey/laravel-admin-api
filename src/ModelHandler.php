<?php

namespace MrTimofey\LaravelAdminApi;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use MrTimofey\LaravelAdminApi\Contracts\ConfiguresAdminHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * This class handles all actions performed with any model.
 * You can write your own handler for any model class by extending this class.
 * To attach your handler implementation to your model @see \MrTimofey\LaravelAdminApi\Contracts\HasAdminHandler
 */
class ModelHandler
{
    /**
     * Model item (may be existing or just new instance)
     * @var Model
     */
    protected $item;

    /**
     * API entity name (URL argument), useful with different model contexts
     * @var string
     */
    protected $name;

    /**
     * Model pages title
     * @var string
     */
    protected $title;

    /**
     * Item editing page subtitle
     * @var string
     */
    protected $itemTitle;

    /**
     * Item creating page subtitle
     * @var string
     */
    protected $createTitle;

    /**
     * Request instance
     * @var Request
     */
    protected $req;

    /**
     * Array of allowed actions.
     * @var array|null
     */
    protected $abilities;

    /**
     * Use common policies while authorizing
     * @var bool
     */
    protected $policies = false;

    /**
     * Prefix for policy actions
     * @var string|null
     */
    protected $policiesPrefix;

    /**
     * Field names to perform text search on
     * @var string[]|null
     */
    protected $searchableFields;

    /**
     * Rewritten search callback
     * @var callable|null
     */
    protected $searchCallback;

    /**
     * Query modifiers applying before any other query processing
     * @var callable[]
     */
    protected $preQueryModifiers = [];

    /**
     * Query modifiers applying just before query execution
     * @var callable[]
     */
    protected $postQueryModifiers = [];

    /**
     * Fields used as filters in index page
     * @var array|null
     */
    protected $filterFields;

    /**
     * Fields exposed in index response
     * @var array|null
     */
    protected $indexFields;

    /**
     * Fields exposed in item response
     * @var array|null
     */
    protected $itemFields;

    /**
     * Validation rules
     * @var array
     */
    protected $validationRules = [];

    /**
     * Validation messages
     * @var array
     */
    protected $validationMessages = [];

    /**
     * Rewritten validation function
     * @var callable|null
     */
    protected $validationCallback;

    public function __construct(Model $item, string $name, Request $req)
    {
        $this->item = $item;
        $this->name = $name;
        // workaround to make multipart/form-data requests type safe since JSON is type safe
        // frontend can send any files + __json_data field to pass any other data
        if ($req->filled('__json_data') && starts_with($req->header('Content-Type', ''), 'multipart/form-data')) {
            $req = clone $req;
            $req->request->add(json_decode($req->get('__json_data'), true));
        }
        $this->req = $req;
        $this->setItem($item);
    }

    /**
     * Name is defined by URL chunk and used in API routes.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set a model item to work with.
     * @param Model $item
     */
    public function setItem(Model $item): void
    {
        $this->item = $item;
        if ($item instanceof ConfiguresAdminHandler) {
            $item->configureAdminHandler($this);
        }
    }

    /**
     * Set model title.
     * @param string $title
     * @return ModelHandler
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set model editing page subtitle.
     * @param string $title
     * @return ModelHandler
     */
    public function setItemTitle(string $title): self
    {
        $this->itemTitle = $title;
        return $this;
    }

    /**
     * Set model creating page subtitle.
     * @param string $title
     * @return ModelHandler
     */
    public function setCreateTitle(string $title): self
    {
        $this->createTitle = $title;
        return $this;
    }

    /**
     * Set array of allowed actions (index, create, simpleCreate, update, destroy, [...custom actions]).
     * @param array $abilities
     * @return ModelHandler
     */
    public function allowActions(array $abilities): self
    {
        $this->abilities = $abilities;
        return $this;
    }

    /**
     * Tell handler to use policies while authorizing actions (all actions are allowed by default).
     * @param bool $use
     * @param null|string $prefix policy method name prefix
     * @return ModelHandler
     */
    public function usePolicies(bool $use = true, ?string $prefix = null): self
    {
        $this->policies = $use;
        $this->policiesPrefix = $prefix;
        return $this;
    }

    /**
     * Set fields which will be used in a text search with SQL LIKE.
     * @param array $fields
     * @return ModelHandler
     */
    public function setSearchableFields(array $fields): self
    {
        $this->searchableFields = $fields;
        return $this;
    }

    /**
     * Set custom search callback to replace default behavior.
     * @param callable $callback function(Builder, Request, array $searchableFields)
     * @return ModelHandler
     */
    public function setSearchCallback(callable $callback): self
    {
        $this->searchCallback = $callback;
        return $this;
    }

    /**
     * Add query modifier called just after Model::newQuery() is called.
     * @param callable $modifier function(Builder, Request)
     * @return ModelHandler
     */
    public function addPreQueryModifier(callable $modifier): self
    {
        $this->preQueryModifiers[] = $modifier;
        return $this;
    }

    /**
     * Add query modifier called just before execution.
     * @param callable $modifier $modifier function(Builder, Request)
     * @return ModelHandler
     */
    public function addPostQueryModifier(callable $modifier): self
    {
        $this->postQueryModifiers[] = $modifier;
        return $this;
    }

    /**
     * Set available filters definition.
     * @param array $fields
     * @return ModelHandler
     */
    public function setFilterFields(array $fields): self
    {
        $this->filterFields = $this->prepareFields($fields);
        return $this;
    }

    /**
     * Set fields available in model index.
     * @param array $fields
     * @param array|null $defaults
     * @return ModelHandler
     */
    public function setIndexFields(array $fields, ?array $defaults = null): self
    {
        $this->indexFields = $this->prepareFields($fields, $defaults ?? ['sortable' => true]);
        return $this;
    }

    /**
     * Set fields available in model creating/editing.
     * @param array $fields
     * @return ModelHandler
     */
    public function setItemFields(array $fields): self
    {
        $this->itemFields = $this->prepareFields($fields);
        return $this;
    }

    /**
     * Set validation rules.
     * Use 'files.{field name}' keys to apply uploaded files validation.
     * @param array $rules
     * @return self
     */
    public function setValidationRules(array $rules): self
    {
        $this->validationRules = $rules;
        return $this;
    }

    /**
     * Set validation messages.
     * @param array $messages
     * @return ModelHandler
     */
    public function setValidationMessages(array $messages): self
    {
        $this->validationMessages = $messages;
        return $this;
    }

    /**
     * Set custom validation callback to replace default behavior.
     * @param callable $validator function(Request, array $rules, array $messages, array $customAttributes)
     * @return ModelHandler
     */
    public function setValidationCallback(callable $validator): self
    {
        $this->validationCallback = $validator;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getItemTitle(): ?string
    {
        return $this->itemTitle;
    }

    public function getCreateTitle(): ?string
    {
        return $this->createTitle;
    }

    /**
     * Get index fields.
     * This method will try to guess about them using $visible model fields if index fields are not set explicitly.
     * @return array|null
     */
    public function getIndexFields(): ?array
    {
        if ($this->indexFields) {
            return $this->indexFields;
        }
        if (($visible = $this->item->getVisible()) && \count($visible) > 0) {
            return $this->prepareFields($visible, ['sortable' => true]);
        }
        return null;
    }

    /**
     * Get item editing fields.
     * This method will try to guess about them using $fillable model fields if item fields are not set explicitly.
     * @return array|null
     */
    public function getItemFields(): ?array
    {
        if ($this->itemFields) {
            return $this->itemFields;
        }
        if (($fillable = $this->item->getFillable()) && \count($fillable) > 0) {
            return $this->prepareFields($fillable);
        }
        return null;
    }

    public function getValidationRules(): ?array
    {
        return $this->validationRules;
    }

    public function getFilterFields(): ?array
    {
        return $this->filterFields;
    }

    public function isSearchable(): bool
    {
        return $this->searchableFields && \count($this->searchableFields) > 0 || $this->searchCallback;
    }

    /**
     * Authorize action.
     * Throw 403 exception if user is not permitted to perform this action.
     * @param string $action
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function authorize(string $action): void
    {
        if ($this->policies) {
            /** @var Authorizable $user */
            $user = $this->req->user();
            if (!$user->can(
                $this->policiesPrefix ? ($this->policiesPrefix . studly_case($action)) : $action,
                $this->item)) {
                throw new AccessDeniedHttpException($action . ' action on ' . $this->name . ' is not authorized');
            }
        }
        if ($this->abilities && !\in_array($action, $this->abilities, true)) {
            throw new AccessDeniedHttpException($action . ' action on ' . $this->name . ' is not allowed');
        }
    }

    /**
     * @param Builder $q
     * @param callable[] $modifiers
     */
    protected function applyQueryModifiers(Builder $q, array $modifiers): void
    {
        foreach ($modifiers as $modifier) {
            $modifier($q, $this->req);
        }
    }

    /**
     * Fill some required but not explicitly set information for each field.
     * Field type information is based on $casts, $dates, $hidden and relation method existence.
     * Title is generated from field name by converting it to title case.
     * @param array $fields
     * @param array $default
     * @return array
     */
    protected function prepareFields(array $fields, array $default = []): array
    {
        $realFields = [];
        $casts = $this->item->getCasts();
        $dates = $this->item->getDates();
        $hidden = $this->item->getHidden();
        foreach ($fields as $field => $conf) {
            if (is_numeric($field)) {
                $field = $conf;
                $conf = $default;
            }
            if (!isset($conf['type'])) {
                if (\in_array($field, $dates, true)) {
                    $conf['type'] = 'datetime';
                } elseif (isset($casts[$field])) {
                    $conf['type'] = $casts[$field];
                } elseif (\in_array($field, $hidden, true)) {
                    $conf['type'] = 'password';
                } elseif (method_exists($this->item, $field)) {
                    $conf['type'] = 'relation';
                    $relation = $this->item->$field();
                    if ($relation instanceof HasMany || $relation instanceof BelongsToMany) {
                        $conf['multiple'] = true;
                    }
                    if (!isset($conf['entity'])) {
                        $conf['entity'] = str_replace('_', '-', $relation->getModel()->getTable());
                    }
                }
            }
            if (!isset($conf['title']) && !isset($conf['placeholder']) && !isset($conf['label'])) {
                $conf['title'] = title_case(preg_replace('/[\_\-\s]+/', ' ', $field));
            }
            $realFields[$field] = $conf;
        }
        return $realFields;
    }

    protected function applyPreQueryModifiers(Builder $q): void
    {
        $this->applyQueryModifiers($q, $this->preQueryModifiers);
    }

    protected function applyPostQueryModifiers(Builder $q): void
    {
        $this->applyQueryModifiers($q, $this->postQueryModifiers);
    }

    /**
     * Apply query filters from request query string.
     * Supported actions:
     *  - filters[field]=value - field equals to value
     *  - filters[!field]=value - field not equals to value
     *  - filters[>~field]=value - field is more than or equals to value
     *  - filters[<~field]=value - field is less than or equals to value
     *  - filters[>field]=value - field is more than value
     *  - filters[<field]=value - field is less than value
     *  - filters[field][]=value1&filters[field][]=value2... - field equals to one of provided values (IN)
     *  - filters[!field][]=value1&filters[!field][]=value2... - field not equals to any of provided values (NOT IN)
     *  - filters[field] - field value is not null (IS NOT NULL)
     *  - filters[!field] - field value is null (IS NULL)
     * @param Builder $q
     */
    protected function applyFilters(Builder $q): void
    {
        if ($filters = (array)$this->req->get('filters')) {
            foreach ($filters as $field => $value) {
                if (is_numeric($field)) {
                    $field = $value;
                    $value = null;
                }

                $not = false;
                $op = '=';

                if (starts_with($field, '!')) {
                    $not = true;
                    $op = '!=';
                    $field = substr($field, 1);
                } elseif (starts_with($field, '>~')) {
                    $op = '>=';
                    $field = substr($field, 2);
                } elseif (starts_with($field, '<~')) {
                    $op = '<=';
                    $field = substr($field, 2);
                } elseif (starts_with($field, '>')) {
                    $op = '>';
                    $field = substr($field, 1);
                } elseif (starts_with($field, '<')) {
                    $op = '<';
                    $field = substr($field, 1);
                }

                if (method_exists($this->item, $field)) {
                    if ($value === null) {
                        $not ? $q->doesntHave($field) : $q->has($field);
                    } elseif (!\is_array($value) || !empty($value)) {
                        $q->whereHas($field, function (Builder $q) use ($value) {
                            $q->whereKey($value);
                        });
                    }
                } elseif (\is_array($value)) {
                    $q->whereIn($field, $value, 'and', $not);
                } elseif ($value === null) {
                    $q->where($field, $not ? false : true);
                } else {
                    $q->where($field, $op, $value);
                }
            }
        }
    }

    /**
     * Apply model scopes from request query string.
     * Syntax:
     *  - ?scopes[scopeName] - scope without parameters
     *  - ?scopes[scopeName][]=param1&scopes[scopeName][]=param2 - call scopeName('param1', 'param2')
     * @param Builder $q
     */
    protected function applyScopes(Builder $q): void
    {
        if ($scopes = (array)$this->req->get('scopes')) {
            foreach ($scopes as $scope => $params) {
                if (is_numeric($scope)) {
                    $scope = $params;
                    $params = [];
                } elseif (!\is_array($params)) {
                    $params = explode(',', $params);
                }

                $scopeMethod = 'scope' . studly_case($scope);
                if (method_exists($model = $q->getModel(), $scopeMethod)) {
                    $model->$scopeMethod($q, ...$params);
                }
            }
        }
    }

    /**
     * Simple text search with LIKE.
     * Uses predefined field list or callback.
     * @param Builder $q
     */
    protected function applySearch(Builder $q): void
    {
        if (!$this->isSearchable()) {
            return;
        }
        if ($this->searchCallback) {
            ($this->searchCallback)($q, $this->req, $this->searchableFields);
        } elseif ($this->searchableFields && ($search = $this->req->get('search'))) {
            $search = mb_strtolower($search);
            $q->where(function (Builder $q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhereRaw('lower(' . $field . ') like ?', ['%' . $search . '%']);
                }
            });
        }
    }

    /**
     * Apply simple sorting based on request query string.
     * Syntax:
     *  - sort[field] - order by asc
     *  - sort[field]=asc|desc|0|1 - (0 - desc, 1 - asc)
     * @param Builder $q
     */
    protected function applySort(Builder $q): void
    {
        if ($sort = $this->req->get('sort')) {
            $sort = (array)$sort;
            foreach ($sort as $k => $v) {
                if (is_numeric($k)) {
                    $field = $v;
                    $asc = true;
                } else {
                    $field = $k;
                    $asc = $v === '0' || $v === 'asc';
                }

                $q->orderBy($field, $asc ? 'asc' : 'desc');
            }
        }
    }

    /**
     * Add relations eager loading based on field list.
     * @param Builder $q
     * @param array|null $fields
     */
    protected function loadRelations(Builder $q, ?array $fields): void
    {
        if (!$fields) {
            return;
        }
        $q->with(collect($fields)
            ->keys()
            ->filter(function(string $field) {
                return method_exists($this->item, $field);
            })
            ->toArray()
        );
    }

    /**
     * Use RequestTransformer to transform each value based on field definition before model item is saved.
     * @param Model $item model item about to save
     * @param array $fields fields definition, each field from this array will have corresponding value
     *                      even if there is no such value in the request
     * @return array [field_name => value] map
     * @see RequestTransformer
     */
    protected function transformRequestData(Model $item, array $fields): array
    {
        $data = [];
        $transformer = app(RequestTransformer::class);
        foreach ($fields as $name => $config) {
            $data[$name] = $transformer->transform(
                $name,
                $config['type'] ?? 'text',
                $this->req,
                $item
            );
        }
        return $data;
    }

    public function buildQuery(): Builder
    {
        $q = $this->item->newQuery();
        $this->applyPreQueryModifiers($q);
        $this->applyScopes($q);
        $this->applyFilters($q);
        $this->applySearch($q);
        $this->applySort($q);
        $this->loadRelations($q, $this->getIndexFields());
        $this->applyPostQueryModifiers($q);
        return $q;
    }

    /**
     * Transform model item to API-ready array with all required fields.
     * @param Model $item
     * @param array|null $fields fields definition
     * @param bool $fullRelations load full relation objects or just their IDs
     *              IMPORTANT: editable relation fields are forced to load only IDs since frontend field implementation
     *              should interact with API to load full objects.
     * @return array
     */
    protected function transform(Model $item, ?array $fields, bool $fullRelations = false): array
    {
        $relations = [];
        if ($fields) {
            $visible = [];
            $appends = [];
            foreach ($fields as $field => $config) {
                if (method_exists($item, $field)) {
                    if (!$item->relationLoaded($field)) {
                        $item->load($field);
                    }
                    $related = $item->getRelation($field);
                    if ($fullRelations && empty($config['editable'])) {
                        $relations[$field] = $related;
                    } else {
                        if ($related instanceof Collection) {
                            $relations[$field] = $related->pluck('id')->toArray();
                        } else {
                            $relations[$field] = $related ? $related->getKey() : null;
                        }
                    }
                } else {
                    $visible[] = $field;
                    if ($item->hasGetMutator($field)) {
                        $appends[] = $field;
                    }
                }
            }
            $item->setVisible($visible);
            $item->setAppends($appends);
        }
        $item->addVisible($item->getKeyName());
        return array_merge($item->toArray(), $relations);
    }

    /**
     * @param Model $item
     * @return array
     */
    public function transformIndexItem(?Model $item = null): array
    {
        if (!$item) {
            $item = clone $this->item;
        }
        return $this->transform($item, $this->getIndexFields(), true);
    }

    /**
     * @return array
     */
    public function transformItem(): array
    {
        return $this->transform($this->item, $this->getItemFields());
    }

    public function validate(bool $validateOnlyPresent = false): void
    {
        $fields = $this->getItemFields();
        $rules = $this->validationRules;
        $messages = $this->validationMessages;
        $titles = collect($this->getIndexFields() ?? [])->merge($fields ?? [])
            ->map(function ($field) {
                return $field['title'] ?? $field['label'] ?? $field['placeholder'] ?? null;
            })
            ->filter(function ($title) {
                return $title;
            })
            ->all();

        foreach ($titles as $k => $title) {
            $titles['files__' . $k] = $title;
        }

        if ($this->validationCallback) {
            ($this->validationCallback)($this->req, $rules, $messages, $titles);
        } elseif ($rules) {
            if ($validateOnlyPresent) {
                $presentRules = [];
                foreach ($this->req->keys() as $k) {
                    if (!empty($rules[$k])) {
                        $presentRules[$k] = $rules[$k];
                    }
                }
                $rules = $presentRules;
            }
            $this->req->validate($rules, $messages, $titles);
        }
    }

    protected function syncHasMany(HasMany $rel, $ids): void
    {
        if (!\is_array($ids) && empty($ids)) {
            return;
        }

        $fk = $rel->getForeignKeyName();
        $parentKey = $rel->getParentKey();
        $toSync = $rel->getRelated()->newQuery()->find((array)$ids);
        $toDelete = $rel->get()->keyBy($rel->getRelated()->getKeyName());

        /** @var Model $item */
        foreach ($toSync as $item) {
            if ($item->getAttribute($fk) !== $parentKey) {
                $item->setAttribute($fk, $parentKey);
                $item->save();
            }
            if ($toDelete->has($item->getKey())) {
                $toDelete->forget($item->getKey());
            }
        }

        /** @var Model $item */
        foreach ($toDelete->toArray() as $item) {
            $item->setAttribute($fk, null);
            $item->save();
        }
    }

    protected function fillAndSave(Model $item, array $fields): Model
    {
        $data = $this->transformRequestData($item, $fields);
        $relations = [];
        foreach ($data as $name => $value) {
            $relationName = camel_case($name);
            if (method_exists($item, $relationName)) {
                $relation = $item->$relationName();
                if ($relation instanceof BelongsTo) {
                    $relation->associate($value);
                } else {
                    $relations[] = [$relation, $value];
                }
            } else {
                $item->setAttribute($name, $value);
            }
        }
        $item->saveOrFail();
        foreach ($relations as [$relation, $value]) {
            if ($relation instanceof BelongsToMany) {
                $relation->sync($value);
            } elseif ($relation instanceof HasMany) {
                $this->syncHasMany($relation, $value);
            }
        }
        return $item;
    }

    public function create(): Model
    {
        $this->validate();
        return $this->fillAndSave($this->item->newInstance(), $this->getItemFields());
    }

    public function update(): void
    {
        $this->validate();
        $this->fillAndSave($this->item, $this->getItemFields());
    }

    /**
     * Update item's single field from request.
     * Request data must contain:
     *  - __field = field name
     *  - [files__]field_name = mixed|UploadedFile
     */
    public function fastUpdate(): void
    {
        $this->validate(true);
        $fields = $this->getIndexFields();
        $field = $this->req->get('__field');
        $this->fillAndSave($this->item, [$field => $fields[$field]]);
    }

    public function bulkUpdate(): void
    {}

    /**
     * @throws \Exception
     */
    public function destroy(): void
    {
        $this->item->delete();
    }

    public function bulkDestroy(array $keys): void
    {
        $this->item->newQuery()->whereKey($keys)->delete();
    }

    // public function action{ActionName}(): mixed
}
