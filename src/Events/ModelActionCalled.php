<?php

namespace MrTimofey\LaravelAdminApi\Events;

class ModelActionCalled extends SingleModelEvent {
    /**
     * @var string
     */
    public $actionName;

    public function __construct(string $entity, $userKey, $key, string $name) {
        parent::__construct($entity, $key, $userKey);
        $this->actionName = $name;
    }
}
