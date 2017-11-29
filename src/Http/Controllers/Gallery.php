<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use MrTimofey\LaravelAioImages\ImageModel as Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class Gallery extends Base
{
    public function upload(): JsonResponse
    {
        $ids = [];
        /** @var UploadedFile $image */
        foreach ((array)$this->req->files->get('images') as $image) {
            $ids[] = Image::upload($image)->id;
        }
        return $this->jsonResponse($ids);
    }
}
