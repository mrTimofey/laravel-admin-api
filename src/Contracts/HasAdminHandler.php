<?php

namespace MrTimofey\LaravelAdminApi\Contracts;

use MrTimofey\LaravelAdminApi\ModelHandler;
use Illuminate\Http\Request;

/**
 * Implement this interface by any of your models to attach your own ModelHandler implementation.
 * @see ModelHandler
 */
interface HasAdminHandler
{
    /**
     * Return custom AdminHandler instance for this model.
     * @param string $name model alias (url part)
     * @param Request $req request
     * @return ModelHandler
     */
    public function getAdminHandler(string $name, Request $req): ModelHandler;
}
