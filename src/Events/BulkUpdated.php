<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkUpdated extends ModelEvent
{
    /**
     * @var array
     */
    public $changes;

    public function __construct(string $entity, array $changes) {
        parent::__construct($entity);
        $this->changes = $changes;
    }
}