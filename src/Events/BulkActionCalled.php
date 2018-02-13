<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkActionCalled extends ModelEvent
{
    /**
     * Action name
     * @var array
     */
    public $action;

    public function __construct(string $entity, string $action) {
        parent::__construct($entity);
        $this->action = $action;
    }
}
