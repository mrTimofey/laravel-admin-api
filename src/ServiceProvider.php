<?php

namespace MrTimofey\LaravelAdminApi;

use MrTimofey\LaravelAdminApi\Contracts\ModelResolver as ModelResolverContract;
use Illuminate\Support\ServiceProvider as Base;

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
        $this->registerModelResolver();
        $this->registerRequestTransformer();
    }

    public function boot(): void
    {
        $this->config = $this->app->make('config')->get('admin');
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        if (str_contains($this->app->version(), 'Lumen')) {
            $router = $this->app;
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $router->get(config('admin.path') . '[/{rest:.*}]', function () {
                return file_get_contents($this->app->basePath() . '/public/admin-dist/app.html');
            });
        } else {
            $router = $this->app->make('router');
            $router->get($this->config['path'] . '/{rest?}', function () {
                return file_get_contents(public_path('admin-dist/app.html'));
            })->middleware($this->config['middleware'])->where('rest', '.*');
        }

        $router->group([
            'namespace' => $this->controllersNamespace,
            'prefix' => $this->config['api_prefix']
        ], function () use ($router) {
            $router->post('auth', 'Auth@login');
            $router->post('auth/remember', 'Auth@remember');
            $router->get('locale', 'Meta@locale');
            $router->delete('auth', 'Auth@logout');
        });

        $router->group([
            'namespace' => $this->controllersNamespace,
            'prefix' => $this->config['api_prefix'],
            'middleware' => $this->config['api_middleware']
        ], function () use ($router) {
            $router->delete('auth', 'Auth@logout');

            $router->get('meta', 'Meta@meta');
            $router->get('auth/user', 'Auth@user');

            $router->get('entity/{model}', 'Crud@index');
            $router->get('entity/{model}/{id}', 'Crud@item');
            $router->post('entity/{model}', 'Crud@create');
            $router->post('entity/{model}/simple', 'Crud@simpleCreate');
            $router->post('entity/{model}/{id}', 'Crud@update');
            $router->post('entity/{model}/{id}/fast', 'Crud@fastUpdate');
            $router->post('entity/{model}/{id}/action/{action}', 'Crud@itemAction');
            $router->delete('entity/{model}/{id}', 'Crud@destroy');

            $router->get('wysiwyg/images/browse', 'Wysiwyg@browser');
            $router->post('wysiwyg/images/upload', 'Wysiwyg@upload');

            $router->post('gallery', 'Gallery@upload');

            $router->get('common-data', 'CommonData@get');
            $router->post('common-data', 'CommonData@save');
        });
    }

    protected function registerModelResolver(): void
    {
        $this->app->singleton(ModelResolverContract::class, function () {
            return new ModelResolver($this->config['models']);
        });
    }

    public function registerRequestTransformer(): void
    {
        $this->app->singleton(RequestTransformer::class, function () {
            $imageConfig = $this->app->make('config')->get('images');
            return new RequestTransformer(
                $imageConfig['upload_path'],
                $imageConfig['public_path']
            );
        });
    }
}
