<?php

namespace MrTimofey\LaravelAdminApi\Contracts;

use MrTimofey\LaravelAdminApi\ModelHandler;

interface ConfiguresAdminHandler
{
    public function configureAdminHandler(ModelHandler $handler): void;
}
