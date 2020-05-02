<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Intervention\Image\ImageManager;
use MrTimofey\LaravelAioImages\ImageModel as Image;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class Wysiwyg extends Base
{
    public function upload()
    {
        try {
            $this->req->validate(['upload' => config('api_admin.wysiwyg.image_upload_rules', 'image')]);
            $file = $this->req->file('upload');
            $image = Image::upload($file, [
                'name' => 'wysiwyg-' . Str::random(6) . time(),
                'ext' => $file->getClientOriginalExtension()
            ]);
            $size = config('api_admin.wysiwyg.image_upload_size');
            if ($size && \count($size) > 0) {
                $target = $image->getAbsPath();
                $interventionImage = $intervention->make($target);
                $interventionImage->fit($size[0], $size[1] ?? null);
                $interventionImage->save($target);
            }
            return $this->jsonResponse(['url' => $image->getPath()]);
        } catch (ValidationException $e) {
            return $this->jsonResponse(['error' => ['message' => implode(', ', $e->errors())]]);
        } catch (FileException $e) {
            return $this->jsonResponse(['error' => ['message' => trans('admin_api::messaages.wysiwyg_upload_file_error')]]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['error' => ['message' => trans('admin_api::messaages.wysiwyg_upload_server_error')]]);
        }
    }

    public function browser(): View
    {
        return view('admin_api::wysiwyg_browser', [
            'images' => Image::query()->where('id', 'like', 'wysiwyg-%')->get(),
            'func_num' => $this->req->get('CKEditorFuncNum'),
            'thumb_pipe' => 'admin-thumb'
        ]);
    }
}
