<?php

namespace MrTimofey\LaravelAdminApi\Events;

abstract class SingleModelEvent extends ModelEvent
{
    /**
     * @var mixed
     */
    public $key;

    public function __construct(string $entity, $key)
    {
        parent::__construct($entity);
        $this->entity = $entity;
    }

    public function getModelInstance()
    {
        return parent::getModel()->newQuery()->find($this->key);
    }
}