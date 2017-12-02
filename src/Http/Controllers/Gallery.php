<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use MrTimofey\LaravelAioImages\ImageModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class Gallery extends Base
{
    public function upload(): JsonResponse
    {
        $ids = [];
        foreach ($this->req->allFiles() as $file) {
            if (\is_array($file)) {
                foreach ($file as $_file) {
                    $ids[] = ImageModel::upload($_file)->id;
                }
            } else {
                $ids[] = ImageModel::upload($file)->id;
            }
        }
        return $this->jsonResponse($ids);
    }
}
