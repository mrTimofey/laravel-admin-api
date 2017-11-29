<?php

namespace App\Admin\Contracts;

use App\Admin\ModelHandler;

interface ConfiguresAdminHandler
{
    public function configureAdminHandler(ModelHandler $handler): void;
}
