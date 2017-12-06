<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use MrTimofey\LaravelAioImages\ImageModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Gallery extends Base
{
    public function upload(): JsonResponse
    {
        $ids = [];
        try {
            foreach ($this->req->allFiles() as $file) {
                if (\is_array($file)) {
                    foreach ($file as $_file) {
                        $ids[] = ImageModel::upload($_file)->id;
                    }
                } else {
                    $ids[] = ImageModel::upload($file)->id;
                }
            }
        } catch (FileNotFoundException $e) {
            throw new BadRequestHttpException(trans('admin_api::messages.upload_bad_file', [], $this->req->getPreferredLanguage()));
        }
        return $this->jsonResponse($ids);
    }
}
