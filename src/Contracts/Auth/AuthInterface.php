<?php

namespace Meraki\Core\Contracts\Auth;

/**
 * @Author DatPA
 */
interface AuthInterface
{
    /**
     * @return mixed
     */
    public function id(): mixed;

    /**
     * @return bool
     */
    public function check(): bool;

    /**
     * @return mixed
     */
    public function user(): mixed;
}
