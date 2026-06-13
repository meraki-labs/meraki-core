<?php
/**
 * @internal
 * Managed by Meraki Core Team
 */

namespace Meraki\Core\Events;

use Meraki\Core\Modules\PermissionRegistry;

class PermissionsRegistered
{
    public PermissionRegistry $registry;

    /**
     * @param PermissionRegistry $registry
     */
    public function __construct(PermissionRegistry $registry)
    {
        $this->registry = $registry;
    }
}
