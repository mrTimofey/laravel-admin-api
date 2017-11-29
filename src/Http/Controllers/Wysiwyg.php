<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use MrTimofey\LaravelAioImages\ImageModel as Image;

class Wysiwyg extends Base
{
    public function upload() {
        $funcNum = $this->req->get('CKEditorFuncNum');
        try {
            $this->req->validate(['upload' => ['image', 'max:4096']]);
            $image = Image::upload($this->req->file('upload'), ['wysiwyg' => true, 'ext' => $file->getClientOriginalExtension()]);
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
            'images' => Image::where('props->wysiwyg', true)->get(),
            'func_num' => $this->req->get('CKEditorFuncNum')
        ]);
    }
}
