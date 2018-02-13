<?php

namespace MrTimofey\LaravelAdminApi\Events;

class ModelActionCalled extends SingleModelEvent {
    /**
     * @var string
     */
    public $actionName;

    public function __construct(string $entity, $userKey, $key, string $name) {
        parent::__construct($entity, $userKey, $key);
        $this->actionName = $name;
    }
}
