<?php

namespace MrTimofey\LaravelAdminApi\Events;

class ModelActionCalled extends SingleModelEvent {
    /**
     * @var string
     */
    public $name;

    public function __construct(string $entity, $key, string $name) {
        parent::__construct($entity, $key);
        $this->name = $name;
    }
}
