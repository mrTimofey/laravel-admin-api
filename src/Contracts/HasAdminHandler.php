<?php

namespace MrTimofey\LaravelAdminApi\Contracts;

use MrTimofey\LaravelAdminApi\ModelHandler;
use Illuminate\Http\Request;

interface HasAdminHandler
{
    /**
     * Specify AdminHandler for this model.
     * @param string $name model alias (url part)
     * @param Request $req request
     * @return ModelHandler
     */
    public function getAdminHandler(string $name, Request $req): ModelHandler;
}
