<?php

namespace MrTimofey\LaravelAdminApi\Contracts;

interface HasCustomChanges
{
    /**
     * Return array of changed fields and their changes.
     * @return array
     */
    public function getCustomChanges(): array;
}