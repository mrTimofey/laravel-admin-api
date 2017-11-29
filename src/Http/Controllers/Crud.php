<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use MrTimofey\LaravelAdminApi\ModelHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Crud extends Base
{
    protected function resolveHandler(Model $instance, string $modelName): ModelHandler
    {
        return $this->resolver->resolveHandler($modelName, $instance);
    }

    /**
     * Resolve model or throw exception if no model/instance resolved.
     * @param string $modelName
     * @param mixed|null $id
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function resolveModel(string $modelName, $id = null): Model
    {
        $instance = $this->resolver->resolveModel($modelName);
        if (!$instance) {
            throw new NotFoundHttpException('Can not resolve model by string "' . $modelName . '"');
        }
        if ($id !== null) {
            return $instance->newQuery()->findOrFail($id);
        }
        return $instance;
    }

    /**
     * Get model paginated results.
     * @param string $modelName
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function index(string $modelName): JsonResponse
    {
        $instance = $this->resolveModel($modelName);
        $handler = $this->resolveHandler($instance, $modelName);
        $handler->authorize('index');
        $q = $handler->buildQuery();
        $total = $q->toBase()->getCountForPagination();
        $items = $q->forPage(
            $page = $this->page(),
            $perPage = $this->perPage()
        )->get()->map(function(Model $item) use ($handler) {
            return $handler->transformIndexItem($item);
        });
        return $this->paginatedResponse(
            new LengthAwarePaginator($items, $total, $perPage, $page)
        );
    }

    /**
     * Get detailed model item information.
     * @param string $modelName
     * @param $id
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function item(string $modelName, $id): JsonResponse
    {
        $instance = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($instance, $modelName);
        $handler->authorize('item');
        return $this->jsonResponse($handler->transformItem());
    }

    /**
     * Call model handler method named 'action{ActionName}()'.
     * @param string $modelName
     * @param $id
     * @param string $action action name
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function itemAction(string $modelName, $id, string $action): JsonResponse
    {
        $instance = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($instance, $modelName);
        $method = 'action' . studly_case($action);
        if (!method_exists($handler, $method)) {
            throw new NotFoundHttpException($modelName . ' handler does not have method ' . $method);
        }
        $handler->authorize($action);
        return $this->jsonResponse($handler->$method());
    }

    /**
     * Create new model item.
     * @param string $modelName
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Throwable
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function create(string $modelName): JsonResponse
    {
        $instance = $this->resolveModel($modelName);
        $handler = $this->resolveHandler($instance, $modelName);
        $handler->authorize('create');
        $item = $handler->create();
        $handler->setItem($item->fresh());
        return $this->jsonResponse($handler->transformItem());
    }

    /**
     * Update model item.
     * @param string $modelName
     * @param $id
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Throwable
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function update(string $modelName, $id): JsonResponse
    {
        $item = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($item, $modelName);
        $handler->authorize('update');
        $handler->update();
        $handler->setItem($item->fresh());
        return $this->jsonResponse($handler->transformItem());
    }

    /**
     * Update single field value of a model item.
     * @param string $modelName
     * @param $id
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function fastUpdate(string $modelName, $id): JsonResponse
    {
        $item = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($item, $modelName);
        $handler->authorize('update');
        $handler->fastUpdate();
        $handler->setItem($item->fresh());
        return $this->jsonResponse($handler->transformIndexItem());
    }

    /**
     * Delete model item.
     * @param string $modelName
     * @param $id
     * @throws \Exception
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function destroy(string $modelName, $id): void
    {
        $instance = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($instance, $modelName);
        $handler->authorize('destroy');
        $handler->destroy();
    }
}
