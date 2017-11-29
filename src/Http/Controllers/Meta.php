<?php

namespace App\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;

class Meta extends Base
{
    public function locale(): JsonResponse
    {
        return json_response([
            'locale' => config('admin.locale', config('app.locale', 'en')),
            'fallback_locale' => config('admin.fallback_locale', config('app.fallback_locale', 'en'))
        ]);
    }

    public function meta(): JsonResponse
    {
        return $this->jsonResponse([
            'nav' => config('admin.nav'),
            'entities' => $this->resolver->getMeta()
        ]);
    }
}
