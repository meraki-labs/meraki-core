<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;

final class CreateAdminStep implements Step
{
    public function run(InstallerContext $context): void
    {
        $adminData = $context->get('admin');

        if (!$adminData || !class_exists(\Meraki\Packages\Auth\Models\User::class)) {
            return;
        }

        \Meraki\Packages\Auth\Models\User::create([
            'name'     => $adminData['name'],
            'email'    => $adminData['email'],
            'password' => $adminData['password_hash'],
        ]);
    }
}
