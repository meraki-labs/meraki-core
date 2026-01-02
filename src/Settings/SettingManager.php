<?php

namespace Meraki\Core\Setting;

class SettingManager
{
    public function __construct(
        protected SettingRepositoryInterface $repository
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->repository->set($key, $value);
    }
}
