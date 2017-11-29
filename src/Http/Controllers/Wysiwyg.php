<?php

namespace App\Admin\Http\Controllers;

use App\Images\Image;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Intervention\Image\ImageManager;

class Wysiwyg extends Base
{
    public function upload(ImageManager $manager) {
        $funcNum = $this->req->get('CKEditorFuncNum');
        try {
            $this->req->validate(['upload' => ['image', 'max:4096']]);
            $file = $this->req->file('upload');
            $image = $manager->make($file)->widen(config('admin.wysiwyg.image_upload_width', 1280),
                function($constraint) { $constraint->upsize(); });
            $image = Image::upload($image, ['wysiwyg' => true, 'ext' => $file->getClientOriginalExtension()]);
            return '<script>window.parent.CKEDITOR.tools.callFunction(\'' . $funcNum . '\', \'' . $image->getPath() . '\');</script>';
        }
        catch (ValidationException $e) {
            return '<script>window.parent.CKEDITOR.tools.callFunction(\'' . $funcNum . '\', null, ' .
                json_encode(implode(', ', $e->errors())) . ');</script>';
        }
        catch (\Throwable $e) {
            return '<script>window.parent.CKEDITOR.tools.callFunction(\'' . $funcNum . '\', null, \'Ошибка при загрузке файла\');</script>';
        }
    }

    public function browser(): View
    {
        return view('wysiwyg-browser', [
            'images' => Image::forWysiwyg()->get(),
            'func_num' => $this->req->get('CKEditorFuncNum')
        ]);
    }
}
