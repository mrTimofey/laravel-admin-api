<?php

namespace App\Admin;

use App\Images\Image;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class RequestTransformer
{
    /**
     * File upload path
     *
     * @var string
     */
    protected $uploadPath;

    /**
     * Public uploaded files path
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Custom transformers
     *
     * @var callable[]
     */
    protected $customTransformers = [];

    public function __construct(string $uploadPath, string $publicPath)
    {
        $this->uploadPath = $uploadPath;
        $this->publicPath = $publicPath;
    }

    /**
     * Register custom transformer.
     * @param string $type field type
     * @param callable $callback function($value, $name, Request, $type)
     */
    public function extend(string $type, callable $callback): void
    {
        $this->customTransformers[$type] = $callback;
    }

    /**
     * Transform input field.
     * @param string $name request field name
     * @param string $type field type
     * @param Request $req request object
     * @return mixed
     */
    public function transform(string $name, string $type, Request $req)
    {
        // call custom callback function
        if (!empty($this->customTransformers[$type])) {
            return $this->customTransformers[$type]($req->get($name), $name, $req, $type);
        }

        // call local method
        $method = 'process' . studly_case($type);
        if (method_exists($this, $method)) {
            return $this->$method($name, $req);
        }

        // return as-is
        $v = $req->get($name);
        if (is_numeric($v)) {
            return $v;
        }
        return $v ?: null;
    }

    protected function upload(UploadedFile $file): string
    {
        $name = time() . str_random(12) . '.' . ($file->getClientOriginalExtension() ?: $file->guessExtension());
        $file->move($this->uploadPath, $name);
        return $this->publicPath . '/' . $name;
    }

    /**
     * Upload file and return field value (relative public path for files and image ID for images).
     * @param string $name
     * @param Request $req
     * @param bool $image is image
     * @return null|string|array
     * @throws \Throwable
     * @throws \Exception
     */
    protected function processFile(string $name, Request $req, $image = false)
    {
        $files = $req->file('files__' . $name);
        if (!$files) {
            return $req->get($name) ?: null;
        }
        if (\is_array($files)) {
            $res = [];
            foreach ($files as $file) {
                try {
                    $uploaded = $image ? Image::upload($file)->id : $this->upload($file);
                    $req[] = $uploaded;
                }
                catch (\Throwable $e) {}
            }
            return $res;
        }
        return $image ? Image::upload($files)->id : $this->upload($files);
    }

    /**
     * Create an image model item.
     * @param string $name
     * @param Request $req
     * @return array|null|string
     * @throws \Throwable
     * @throws \Exception
     */
    protected function processImage(string $name, Request $req)
    {
        return $this->processFile($name, $req, true);
    }

    protected function processGallery(string $name, Request $req)
    {
        $images = $req->get($name) ?: [];
        return array_combine($images, array_map(function($sort) {
            return ['sort' => (int)$sort];
        }, array_keys($images)));
    }

    protected function processBoolean(string $name, Request $req): bool
    {
        return (bool)$req->get($name);
    }

    protected function processBool(string $name, Request $req): bool
    {
        return $this->processBoolean($name, $req);
    }
}
