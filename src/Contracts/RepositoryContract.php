<?php

declare(strict_types=1);

namespace Meraki\Core\Contracts;

interface RepositoryContract
{
    public function findById(int|string $id): ?object;

    /** @return iterable<object> */
    public function findAll(array $filters = [], int $perPage = 15): iterable;

    public function create(array $data): object;

    public function update(int|string $id, array $data): object;

    public function delete(int|string $id): bool;

    public function exists(int|string $id): bool;
}
