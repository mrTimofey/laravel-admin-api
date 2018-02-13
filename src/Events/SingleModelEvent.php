<?php

namespace MrTimofey\LaravelAdminApi\Events;

abstract class SingleModelEvent {
    /**
     * @var string
     */
    public $entity;

    /**
     * @var mixed
     */
    public $key;

    public function __construct(string $entity, $key) {
        $this->entity = $entity;
        $this->key = $key;
    }
}