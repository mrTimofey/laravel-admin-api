<?php

namespace MrTimofey\LaravelAdminApi\Events;

class ModelUpdated extends SingleModelEvent
{
    /**
     * Changed fields
     *  [
     *      fieldName =>
     *          // for fields and BelongsTo relations
     *          [oldValue, newValue]
     *          // for HasMany and BelongsToMany relations
     *          ['attached' => [key1, key2, ...], 'detached' => [...]. 'updated' => [...]
     *  ]
     * @var array
     */
    public $changes;

    public function __construct(string $entity, $userKey, $key, array $changes) {
        parent::__construct($entity, $userKey, $key);
        $this->changes = $changes;
    }

    public function getArgs(): ?array
    {
        return [
            'changes' => $this->changes
        ];
    }
}
