<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

class View extends Base
{
    public function app(): string
    {
        return file_get_contents(config('admin_api.frontend_entry'));
    }
}
