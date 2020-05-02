<?php /** @noinspection PhpUnused */

namespace MrTimofey\LaravelAdminApi\Events;

use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use MrTimofey\LaravelAdminApi\Contracts\ModelResolver;

abstract class ModelEvent
{
    /**
     * Entity name
     * @var string
     */
    public $entity;

    /**
     * User ID
     * @var mixed
     */
    public $userKey;

    public function __construct(string $entity, $userKey)
    {
        $this->entity = $entity;
        $this->userKey = $userKey;
    }

    public function getModel(): Model
    {
        /** @var ModelResolver $resolver */
        $resolver = app(ModelResolver::class);
        return $resolver->resolveModel($this->entity);
    }

    /**
     * @return Authenticatable|Model|null
     */
    public function getUser(): ?Authenticatable
    {
        if (!$this->userKey) {
            return null;
        }
        /** @var TokenGuard $guard */
        $guard = auth(config('admin_api.api_guard', 'api'));
        return $guard->getProvider()->retrieveById($this->userKey);
    }

    public function getArgs(): ?array
    {
        return null;
    }
}
