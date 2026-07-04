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

        /*
         * Tự động phát hiện plugins từ vendor/composer/installed.json
         * (packages khai báo extra.meraki.plugin)
         */
        'auto_discover' => env('MERAKI_PLUGIN_AUTO_DISCOVER', true),

        /*
         * Danh sách plugin class đăng ký thủ công
         * Format: ['plugin-name' => PluginClass::class]
         */
        'list' => [],

        /*
         * Plugins bị disable tĩnh (override database state)
         * Dùng cho environment-level disable (test, staging)
         */
        'disabled' => [],
    ],
    'hub' => [
        'url'     => env('MERAKI_HUB_URL', 'https://hub.merakilabs.tech'),
        'token'   => env('MERAKI_HUB_TOKEN'),
        'timeout' => (int) env('MERAKI_HUB_TIMEOUT', 10),
    ],
];
