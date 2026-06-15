<?php

return [
    'enabled' => env('MERAKI_ENABLED', true),
    'capabilities' => [
        'auth'       => ['driver' => env('MERAKI_AUTH_DRIVER', 'auto')],
        'permission' => ['driver' => env('MERAKI_PERMISSION_DRIVER', 'auto')],
    ],
    'state_file' => base_path('.meraki.json'),
];
