<?php

return [
    'enabled' => env('MERAKI_ENABLED', true),
    'capabilities' => [
        'auth'       => ['driver' => env('MERAKI_AUTH_DRIVER', 'auto')],
        'permission' => ['driver' => env('MERAKI_PERMISSION_DRIVER', 'auto')],
    ],
    'state_file' => base_path('.meraki.json'),
    'plugins' => [
        'path'     => base_path('plugins/'),
        'discover' => ['directory', 'composer'],
    ],
    'hub' => [
        'url'     => env('MERAKI_HUB_URL', 'https://hub.merakilabs.tech'),
        'api_key' => env('MERAKI_HUB_API_KEY'),
        'timeout' => (int) env('MERAKI_HUB_TIMEOUT', 30),
    ],
];
