<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkUpdated
{
    /**
     * @var string
     */
    public $entity;

    /**
     * @var array
     */
    public $changes;

    public function __construct(string $entity, array $changes) {
        $this->entity = $entity;
        $this->changes = $changes;
    }
}