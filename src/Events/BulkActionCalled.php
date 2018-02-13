<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkActionCalled
{
    /**
     * @var string
     */
    public $entity;

    /**
     * Action name
     * @var array
     */
    public $action;

    public function __construct(string $entity, string $action) {
        $this->entity = $entity;
        $this->action = $action;
    }
}
