<?php

namespace App\Admin\Http\Controllers;

use App\Images\Image;
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
