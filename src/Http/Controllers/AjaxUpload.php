<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use MrTimofey\LaravelAioImages\ImageModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AjaxUpload extends Base {
    /**
     * @return UploadedFile[]
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    protected function getFiles(): array
    {
        $files = [];
        try {
            foreach ($this->req->allFiles() as $file) {
                if (\is_array($file)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $files = \array_merge($files, $file);
                } else {
                    /** @var UploadedFile $file */
                    $files[] = $file;
                }
            }
        } catch (FileNotFoundException $e) {
            throw new BadRequestHttpException(trans('admin_api::messages.upload_bad_file', [], $this->req->getPreferredLanguage()));
        }
        return $files;
    }

    /**
     * @return JsonResponse
     * @throws BadRequestHttpException
     */
    public function uploadFiles(): JsonResponse
    {
        $files = [];
        $uploader = app('admin_api:upload');
        foreach ($this->getFiles() as $file) {
            $files[] = $uploader($file);
        }
        return $this->jsonResponse($files);
    }

    /**
     * @return JsonResponse
     * @throws BadRequestHttpException
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @throws \Throwable
     */
    public function uploadImages(): JsonResponse
    {
        $ids = [];
        foreach ($this->getFiles() as $file) {
            $ids[] = ImageModel::upload($file)->getKey();
        }
        return $this->jsonResponse($ids);
    }
}