<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkActionCalled extends ModelEvent
{
    /**
     * Action name
     * @var array
     */
    public $actionName;

    public function __construct(string $entity, $userKey, string $name) {
        parent::__construct($entity, $userKey);
        $this->actionName = $name;
    }
}
