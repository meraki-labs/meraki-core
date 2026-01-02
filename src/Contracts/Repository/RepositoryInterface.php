<?php

namespace Meraki\Core\Contracts\Repository;

interface RepositoryInterface
{
    public function find(mixed $id): mixed;

    public function findOrFail(mixed $id): mixed;

    public function paginate(int $perPage = 15): mixed;
}
