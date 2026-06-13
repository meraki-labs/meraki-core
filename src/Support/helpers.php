<?php
/**
 * The helper for Meraki Core using for the system
 * @Author DatPA
 */

use Meraki\Core\Modules\PermissionRegistry;


if (!function_exists('get_permissions')) {
    /**
     * @return array
     */
    function get_permissions(): array
    {
        return app(PermissionRegistry::class)->all();
    }
}
