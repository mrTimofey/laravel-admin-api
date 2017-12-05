<?php

namespace MrTimofey\LaravelAdminApi\Contracts;

use MrTimofey\LaravelAdminApi\ModelHandler;
use Illuminate\Database\Eloquent\Model;

/**
 * Used with service container to bind ModelResolver class.
 * You can bind your own implementation if needed.
 * @see \MrTimofey\LaravelAdminApi\ModelResolver
 */
interface ModelResolver
{
    /**
     * Resolve model instance by url chunk.
     * @param string $name
     * @return Model|null
     */
    public function resolveModel(string $name): ?Model;

    /**
     * Resolve ModelHandler instance by url chunk.
     * @param string $name
     * @param Model $instance
     * @return ModelHandler
     */
    public function resolveHandler(string $name, Model $instance): ModelHandler;

    /**
     * Return meta information about each model.
     * @return array
     */
    public function getMeta(): array;
}
