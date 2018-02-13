<?php

namespace MrTimofey\LaravelAdminApi\Events;

use Illuminate\Database\Eloquent\Model;
use MrTimofey\LaravelAdminApi\Contracts\ModelResolver;

abstract class ModelEvent
{
    public $entity;

    public function __construct(string $entity)
    {
        $this->entity = $entity;
    }

    public function getModel(): Model
    {
        /** @var ModelResolver $resolver */
        $resolver = app(ModelResolver::class);
        return $resolver->resolveModel($this->entity);
    }
}
