<?php

namespace Meraki\Core\Contracts\Repository;

/**
 * @Author DatPA
 */
interface RepositoryInterface
{
    /**
     * @param mixed $id
     * @return mixed
     */
    public function find(mixed $id): mixed;

    /**
     * @param mixed $id
     * @return mixed
     */
    public function findOrFail(mixed $id): mixed;

    /**
     * @param int $perPage
     * @return mixed
     */
    public function paginate(int $perPage = 15): mixed;
}
