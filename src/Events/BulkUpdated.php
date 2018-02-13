<?php

namespace MrTimofey\LaravelAdminApi\Events;

class BulkUpdated extends ModelEvent
{
    /**
     * @var array
     */
    public $changes;

    public function __construct(string $entity, $userKey, array $changes) {
        parent::__construct($entity, $userKey);
        $this->changes = $changes;
    }

    public function getArgs(): ?array
    {
        return [
            'changes' => $this->changes
        ];
    }
}