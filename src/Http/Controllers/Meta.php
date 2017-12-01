<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use Illuminate\Http\JsonResponse;

class Meta extends Base
{
    public function locale(): JsonResponse
    {
        return $this->jsonResponse([
            'locale' => config('admin_api.locale', config('app.locale', 'en')),
            'fallback_locale' => config('admin_api.fallback_locale', config('app.fallback_locale', 'en'))
        ]);
    }

    public function meta(): JsonResponse
    {
        return $this->jsonResponse([
            'nav' => config('admin_api.nav'),
            'entities' => $this->resolver->getMeta(),
            'wysiwyg' => config('admin_api.wysiwyg'),
            'upload' => config('admin_api.upload')
        ]);
    }
}
