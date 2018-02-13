<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkDestroyed extends ModelEvent
{
    /**
     * Deleted items keys
     * @var array
     */
    public $keys;

    public function __construct(string $entity, array $keys) {
        parent::__construct($entity);
        $this->keys = $keys;
    }
}
