<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use MrTimofey\LaravelAdminApi\Events\BulkActionCalled;
use MrTimofey\LaravelAdminApi\Events\BulkDestroyed;
use MrTimofey\LaravelAdminApi\Events\ModelActionCalled;
use MrTimofey\LaravelAdminApi\Events\ModelCreated;
use MrTimofey\LaravelAdminApi\Events\ModelDestroyed;
use MrTimofey\LaravelAdminApi\Events\ModelUpdated;
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
     * @throws \RuntimeException
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
        )->get()->map(function (Model $item) use ($handler) {
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
     * @throws \RuntimeException
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
     * Call model handler method named 'bulk{ActionName}()' on a model.
     * @param string $modelName
     * @param string $action action name
     * @return JsonResponse
     * @throws \RuntimeException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function bulkAction(string $modelName, string $action): JsonResponse
    {
        $instance = $this->resolveModel($modelName);
        $handler = $this->resolveHandler($instance, $modelName);
        $action = studly_case($action);
        $method = 'bulk' . $action;
        if (!method_exists($handler, $method)) {
            throw new NotFoundHttpException($modelName . ' handler does not have method ' . $method);
        }
        $handler->authorize($action);
        $res = $handler->$method();
        event(new BulkActionCalled($modelName, $this->req->user()->getKey(), $action));
        return $this->jsonResponse($res);
    }

    /**
     * Call model handler method named 'action{ActionName}()' on a model instance.
     * @param string $modelName
     * @param $id
     * @param string $action action name
     * @return JsonResponse
     * @throws \RuntimeException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function itemAction(string $modelName, $id, string $action): JsonResponse
    {
        $instance = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($instance, $modelName);
        $action = studly_case($action);
        $method = 'action' . $action;
        if (!method_exists($handler, $method)) {
            throw new NotFoundHttpException($modelName . ' handler does not have method ' . $method);
        }
        $handler->authorize($action);
        $res = $handler->$method();
        event(new ModelActionCalled($modelName, $this->req->user()->getKey(), $instance->getKey(), $action));
        return $this->jsonResponse($res);
    }

    /**
     * Create new model item.
     * @param string $modelName
     * @return JsonResponse
     * @throws \RuntimeException
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
        event(new ModelCreated($modelName, $this->req->user()->getKey(), $item->getKey()));
        $handler->setItem($item->fresh());
        return $this->jsonResponse($handler->transformItem());
    }

    /**
     * Update model item.
     * @param string $modelName
     * @param $id
     * @return JsonResponse
     * @throws \RuntimeException
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
        $changes = $handler->getLastSaveChanges();
        if (!empty($changes)) {
            event(new ModelUpdated($modelName, $this->req->user()->getKey(), $item->getKey(), $changes));
        }
        $handler->setItem($item->fresh());
        return $this->jsonResponse($handler->transformItem());
    }

    /**
     * Update single field value of a model item.
     * @param string $modelName
     * @param $id
     * @return JsonResponse
     * @throws \RuntimeException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Throwable
     */
    public function fastUpdate(string $modelName, $id): JsonResponse
    {
        $item = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($item, $modelName);
        $handler->authorize('update');
        $handler->fastUpdate();
        $changes = $handler->getLastSaveChanges();
        if (!empty($changes)) {
            event(new ModelUpdated($modelName, $this->req->user()->getKey(), $item->getKey(), $changes));
        }
        $handler->setItem($item->fresh());
        return $this->jsonResponse($handler->transformIndexItem());
    }

    /**
     * Delete model item.
     * @param string $modelName
     * @param $id
     * @throws \RuntimeException
     * @throws \Exception
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function destroy(string $modelName, $id): void
    {
        $item = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($item, $modelName);
        $handler->authorize('destroy');
        $handler->destroy();
        event(new ModelDestroyed($modelName, $this->req->user()->getKey(), $id));
    }

    /**
     * Delete multiple model items.
     * @param string $modelName
     * @throws \RuntimeException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function bulkDestroy(string $modelName): void
    {
        $instance = $this->resolveModel($modelName);
        $handler = $this->resolveHandler($instance, $modelName);
        $handler->authorize('destroy');
        $destroyed = $handler->bulkDestroy($this->req->get('keys'));
        event(new BulkDestroyed($modelName, $this->req->user()->getKey(), $destroyed));
    }
}
