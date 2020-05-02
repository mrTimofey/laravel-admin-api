<?php

namespace MrTimofey\LaravelAdminApi;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\UploadedFile;
use MrTimofey\LaravelAdminApi\Contracts\ModelResolver as ModelResolverContract;
use Illuminate\Support\ServiceProvider as Base;
use Illuminate\Support\Str;

class ServiceProvider extends Base
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $controllersNamespace = 'MrTimofey\LaravelAdminApi\Http\Controllers';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'admin_api');
        $this->registerModelResolver();
        $this->registerUploadFunction();
        $this->registerRequestTransformer();
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config.php' => config_path('admin_api.php')], 'config');
        $this->loadViewsFrom(__DIR__ . '/../views', 'admin_api');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'admin_api');
        $this->registerImagePipe();
        $this->registerRoutes();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerImagePipe(): void
    {
        $config = $this->app->make('config');
        $this->config = $config->get('admin_api');
        $pipes = $config->get('aio_images.pipes', []);
        if (empty($pipes['admin-thumb'])) {
            $pipes['admin-thumb'] = $this->config['thumbnail_pipe'] ?? [['heighten', 120]];
            $config->set('aio_images.pipes', $pipes);
        }
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerRoutes(): void
    {
        $lumen = Str::contains($this->app->version(), 'Lumen');
        $router = $this->app->make('router');
        $apiPrefix = $this->config['api_prefix'];

        $router->group([
            'namespace' => $this->controllersNamespace,
            'prefix' => $this->config['api_prefix']
        ], function () use ($router) {
            $router->post('auth', 'Auth@authenticate')->middleware($this->config['auth_middleware']);
            $router->get('locale', 'Meta@locale');
        });

        $router->group([
            'namespace' => $this->controllersNamespace,
            'prefix' => $apiPrefix,
            'middleware' => $this->config['api_middleware']
        ], static function () use ($router) {
            $router->delete('auth', 'Auth@logout');

            $router->get('meta', 'Meta@meta');
            $router->get('auth/user', 'Auth@user');

            $router->get('entity/{model}', 'Crud@index');
            $router->delete('entity/{model}', 'Crud@bulkDestroy');
            $router->post('entity/{model}', 'Crud@create');
            $router->get('entity/{model}/{id}', 'Crud@item');
            $router->post('entity/{model}/{id}', 'Crud@update');
            $router->post('entity/{model}/{id}/fast', 'Crud@fastUpdate');
            $router->post('entity/{model}/action/{action}', 'Crud@bulkAction');
            $router->post('entity/{model}/{id}/action/{action}', 'Crud@itemAction');
            $router->delete('entity/{model}/{id}', 'Crud@destroy');

            $router->get('wysiwyg/images/browse', 'Wysiwyg@browser');
            $router->post('wysiwyg/images/upload', 'Wysiwyg@upload');

            $router->post('upload/files', 'AjaxUpload@uploadFiles');
            $router->post('upload/images', 'AjaxUpload@uploadImages');
            $router->post('gallery', 'AjaxUpload@uploadImages');
        });

        // register catch-all route on booted event to prevent triggering before any other application route
        $this->app->booted(function () use ($lumen, $router) {
            if ($lumen) {
                $router->get($this->config['frontend_path'] . '[/{rest:.*}]',
                    $this->controllersNamespace . '\View@app');
            } else {
                $router->get($this->config['frontend_path'] . '/{rest?}', $this->controllersNamespace . '\View@app')
                    ->where('rest', '.*');
            }
        });
    }

    protected function registerModelResolver(): void
    {
        $this->app->singleton(ModelResolverContract::class, function () {
            return new ModelResolver($this->config['models']);
        });
    }

    protected function registerUploadFunction(): void
    {
        $this->app->singleton('admin_api:upload', function () {
            $config = $this->config['upload'];
            return static function (UploadedFile $file, ?string $name = null) use ($config) {
                if (!$name) {
                    $name = time() . Str::random(12) . '.' . ($file->getClientOriginalExtension() ?: $file->guessExtension());
                }
                $file->move(rtrim($config['path'], '/'), $name);
                return rtrim($config['public_path'], '/') . '/' . $name;
            };
        });
    }

    public function registerRequestTransformer(): void
    {
        $this->app->singleton(RequestTransformer::class, static function () {
            return new RequestTransformer();
        });
    }
}
