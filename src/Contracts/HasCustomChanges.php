<?php

namespace MrTimofey\LaravelAdminApi\Contracts;

/**
 * Provide additional information about model changes on ModelUpdated event.
 * Do not include dirty attributes and changed relations here.
 */
interface HasCustomChanges
{
    /**
     * Return array of changed fields and their changes.
     * @return array
     */
    public function getCustomChanges(): array;
}