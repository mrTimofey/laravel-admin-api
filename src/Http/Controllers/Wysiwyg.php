<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Intervention\Image\ImageManager;
use MrTimofey\LaravelAioImages\ImageModel as Image;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class Wysiwyg extends Base
{
    public function upload(ImageManager $intervention): string
    {
        $funcNum = $this->req->get('CKEditorFuncNum');
        try {
            $this->req->validate(['upload' => config('api_admin.wysiwyg.image_upload_rules', 'image')]);
            $file = $this->req->file('upload');
            $image = Image::upload($file, [
                'name' => 'wysiwyg-' . str_random(6) . time(),
                'ext' => $file->getClientOriginalExtension()
            ]);
            $size = config('api_admin.wysiwyg.image_upload_size');
            if ($size && \count($size) > 0) {
                $target = $image->getAbsPath();
                $interventionImage = $intervention->make($target);
                $interventionImage->fit($size[0], $size[1] ?? null);
                $interventionImage->save($target);
            }
            return '<script>window.parent.CKEDITOR.tools.callFunction(\'' . $funcNum . '\', \'' . $image->getPath() . '\');</script>';
        } catch (ValidationException $e) {
            return '<script>window.parent.CKEDITOR.tools.callFunction(\'' . $funcNum . '\', null, ' .
                json_encode(implode(', ', $e->errors())) . ');</script>';
        } catch (FileException $e) {
            return '<script>window.parent.CKEDITOR.tools.callFunction(\'' . $funcNum . '\', null, \'Could not upload image...\');</script>';
        } catch (\Throwable $e) {
            return '<script>window.parent.CKEDITOR.tools.callFunction(\'' . $funcNum . '\', null, \'Something went wrong...\');</script>';
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
