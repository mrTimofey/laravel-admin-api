<?php

namespace MrTimofey\LaravelAdminApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use MrTimofey\LaravelAioImages\ImageModel as Image;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestTransformer
{
    /**
     * Custom transformers
     * @var callable[]
     */
    protected $customTransformers = [];

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
     * @param Model $item item to be saved
     * @return mixed
     */
    public function transform(string $name, string $type, Request $req, Model $item)
    {
        // call custom callback function
        if (!empty($this->customTransformers[$type])) {
            return $this->customTransformers[$type]($req->get($name), $name, $req, $item, $type);
        }

        // call local method
        $method = 'process' . studly_case($type);
        if (method_exists($this, $method)) {
            return $this->$method($name, $req, $item);
        }

        // return as-is
        $v = $req->get($name);
        if (\is_int($v) || \is_float($v) || \is_bool($v)) {
            return $v;
        }
        return $v ?: null;
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    protected function upload(UploadedFile $file): string
    {
        return app('admin_api:upload')($file);
    }

    /**
     * Upload file and return field value (relative public path for files and image ID for images).
     * @param string $name
     * @param Request $req
     * @param Model $item
     * @param bool $image is image
     * @return null|string|array
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Exception
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     * @throws \Throwable
     */
    protected function processFile(string $name, Request $req, Model $item, bool $image = false)
    {
        $files = $req->file('files__' . $name);
        if (!$files) {
            return $req->get($name) ?: null;
        }
        if (\is_array($files)) {
            $res = [];
            foreach ($files as $file) {
                try {
                    $uploaded = $image ? Image::upload($file)->getKey() : $this->upload($file);
                    $req[] = $uploaded;
                } catch (\Throwable $e) {}
            }
            return $res;
        }
        try {
            return $image ? Image::upload($files)->getKey() : $this->upload($files);
        } catch (FileNotFoundException $e) {
            throw new BadRequestHttpException(trans('admin_api::messages.upload_bad_file', [], $req->getPreferredLanguage()));
        }
    }

    /**
     * Create an image model item.
     * @param string $name
     * @param Request $req
     * @param Model $item
     * @return array|null|string
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Exception
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     * @throws \Throwable
     */
    protected function processImage(string $name, Request $req, Model $item)
    {
        return $this->processFile($name, $req, $item, true);
    }

    protected function processGallery(string $name, Request $req, Model $item): array
    {
        $images = $req->get($name) ?: [];
        /** @var BelongsToMany $rel */
        $rel = $item->$name();
        // prevent update event from triggering
        if ($images === $rel->pluck($rel->getRelated()->getKeyName())->all()) {
            return $images;
        }
        return array_combine($images, array_map(function ($sort) {
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

    protected function processPassword(string $name, Request $req, Model $item): ?string
    {
        $v = $req->get($name);
        return $item->exists && !$v ? $item->getAttribute($name) : $v;
    }

    protected function processInteger(string $name, Request $req): ?int
    {
        $v = $req->get($name);
        return $v === null ? null : (int)$req->get($name);
    }

    protected function processInt(string $name, Request $req): ?int
    {
        return $this->processInteger($name, $req);
    }

    protected function processNumber(string $name, Request $req): ?int
    {
        return $this->processInteger($name, $req);
    }

    protected function processNumeric(string $name, Request $req): ?int
    {
        return $this->processInteger($name, $req);
    }

    protected function processFloat(string $name, Request $req): ?float
    {
        $v = $req->get($name);
        return $v === null ? null : (float)str_replace(',', '.', $v);
    }
}
