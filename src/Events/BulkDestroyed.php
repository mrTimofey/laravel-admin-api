<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkDestroyed extends ModelEvent
{
    /**
     * Deleted items keys
     * @var array
     */
    public $keys;

    public function __construct(string $entity, $userKey, array $keys) {
        parent::__construct($entity, $userKey);
        $this->keys = $keys;
    }

    public function getArgs(): ?array
    {
        return [
            'keys' => $this->keys
        ];
    }
}
