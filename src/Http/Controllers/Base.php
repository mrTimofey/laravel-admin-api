<?php

namespace MrTimofey\LaravelAdminApi\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use MrTimofey\LaravelAdminApi\Contracts\ModelResolver;

abstract class Base extends Controller
{
    public const PER_PAGE_PARAM_NAME = 'limit';
    public const PAGE_PARAM_NAME = 'page';
    public const DEFAULT_PER_PAGE = 25;
    public const MAX_PER_PAGE = 100;

    /**
     * @var Request
     */
    protected $req;

    /**
     * @var ModelResolver
     */
    protected $resolver;

    public function __construct(Request $req, ModelResolver $resolver)
    {
        Model::$snakeAttributes = false;
        $this->req = $req;
        $this->resolver = $resolver;
    }

    /**
     * Json response.
     * @param $data
     * @param int $status
     * @param array $headers
     * @return JsonResponse
     */
    protected function jsonResponse($data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Paginated JsonResponse.
     * @param Paginator|\Illuminate\Database\Query\Builder|Builder $p
     * @param array|Collection|null $add
     * @param int|null $perPage
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse($p, $add = null, ?int $perPage = null): JsonResponse
    {
        // suppose $p is a query builder
        if (!$p instanceof Paginator) {
            /** @var Builder $p */
            $p = $p->paginate($this->perPage($perPage));
        }
        /** @var Paginator $p */

        $data = [
            'pagination' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
            ],
            'items' => $p->items()
        ];

        // add length aware data if presented
        if ($p instanceof LengthAwarePaginator) {
            $data['pagination']['last_page'] = $p->lastPage();
            $data['pagination']['total'] = $p->total();
        }

        // merge with additional data if any
        if ($add && \count($add)) {
            $data = array_merge(
                $data,
                $add instanceof Collection ? $add->all() : $add
            );
        }

        return $this->jsonResponse($data);
    }

    /**
     * Secure per page value.
     * @param null|mixed $arg
     * @param int $max
     * @return int
     */
    protected function perPage($arg = null, int $max = null): int
    {
        if ($arg === null) {
            $arg = $this->req->get(static::PER_PAGE_PARAM_NAME);
            if ($arg === null) {
                return static::DEFAULT_PER_PAGE;
            }
        }
        $arg = (int)$arg;
        $max = $max ?? static::MAX_PER_PAGE;
        if ($arg > $max) {
            return $max;
        }
        if ($arg < 1) {
            return 1;
        }
        return $arg;
    }

    protected function page(): int
    {
        $page = (int)$this->req->get(static::PAGE_PARAM_NAME);
        return $page < 1 ? 1 : $page;
    }
}
