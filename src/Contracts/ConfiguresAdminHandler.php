<?php

namespace MrTimofey\LaravelAdminApi\Contracts;

use MrTimofey\LaravelAdminApi\ModelHandler;

/**
 * Implement this interface by any of your models to provide ModelHandler configuration.
 * @see ModelHandler
 */
interface ConfiguresAdminHandler
{
    /**
     * Configure ModelHandler for this model.
     * @param ModelHandler $handler
     */
    public function configureAdminHandler(ModelHandler $handler): void;
}
