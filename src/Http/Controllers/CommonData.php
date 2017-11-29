<?php

namespace App\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;

class CommonData extends Base
{
    public function get(): JsonResponse
    {
        $data = common_data(null, [
            'home_event' => null
        ]);
        return json_response($data);
    }

    public function save()
    {
        if ($this->req->get('value') === null) {
            common_data_delete($this->req->get('key'));
        } else {
            common_data_set($this->req->get('key'), $this->req->get('value'));
        }
    }
}
