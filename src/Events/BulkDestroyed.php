<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkDestroyed
{
    /**
     * @var string
     */
    public $entity;

    /**
     * Deleted items keys
     * @var array
     */
    public $keys;

    public function __construct(string $entity, array $keys) {
        $this->entity = $entity;
        $this->keys = $keys;
    }
}
