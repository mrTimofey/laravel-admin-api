<?php /** @noinspection PhpUnused */

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
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
     * @throws RuntimeException
     * @throws AccessDeniedHttpException
     * @throws InvalidArgumentException
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
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
        )->get()->map(static function (Model $item) use ($handler) {
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
     * @throws RuntimeException
     * @throws AccessDeniedHttpException
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
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
     * @throws RuntimeException
     * @throws AccessDeniedHttpException
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
     */
    public function bulkAction(string $modelName, string $action): JsonResponse
    {
        $instance = $this->resolveModel($modelName);
        $handler = $this->resolveHandler($instance, $modelName);
        $action = Str::studly($action);
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
     * @throws RuntimeException
     * @throws AccessDeniedHttpException
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
     */
    public function itemAction(string $modelName, $id, string $action): JsonResponse
    {
        $instance = $this->resolveModel($modelName, $id);
        $handler = $this->resolveHandler($instance, $modelName);
        $action = Str::studly($action);
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
     * @throws RuntimeException
     * @throws AccessDeniedHttpException
     * @throws Throwable
     * @throws MassAssignmentException
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
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
     * @throws RuntimeException
     * @throws AccessDeniedHttpException
     * @throws Throwable
     * @throws MassAssignmentException
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
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
     * @throws RuntimeException
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     * @throws Throwable
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
     * @throws RuntimeException
     * @throws Exception
     * @throws ModelNotFoundException
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
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
     * @throws RuntimeException
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws ModelNotFoundException
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
