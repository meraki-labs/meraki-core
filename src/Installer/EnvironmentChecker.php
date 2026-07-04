<?php

namespace Meraki\Core\Installer;

use Illuminate\Support\Facades\DB;

final class EnvironmentChecker
{
    /** @return array<int, array{key: string, label: string, pass: bool, detail: string}> */
    public function run(): array
    {
        $checks = [];

        $checks[] = $this->checkPhpVersion();
        foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'json', 'fileinfo'] as $ext) {
            $checks[] = $this->checkExtension($ext);
        }
        $checks[] = $this->checkWritable('storage/', storage_path(), 'storage/');
        $checks[] = $this->checkWritable('bootstrap/cache/', base_path('bootstrap/cache'), 'bootstrap/cache/');
        $checks[] = $this->checkEnvFile();
        $checks[] = $this->checkAppKey();
        $checks[] = $this->checkDbConnection();

        return $checks;
    }

    public function allPass(): bool
    {
        return collect($this->run())->every(fn ($c) => $c['pass']);
    }

    public function dbConnectionPass(): bool
    {
        $check = $this->checkDbConnection();
        return $check['pass'];
    }

    /** @return array{key: string, label: string, pass: bool, detail: string} */
    private function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        $pass    = version_compare($version, '8.2.0', '>=');

        return [
            'key'    => 'php_version',
            'label'  => 'PHP >= 8.2',
            'pass'   => $pass,
            'detail' => $pass ? "PHP {$version}" : "PHP {$version} (yêu cầu >= 8.2)",
        ];
    }

    /** @return array{key: string, label: string, pass: bool, detail: string} */
    private function checkExtension(string $ext): array
    {
        $pass = extension_loaded($ext);

        return [
            'key'    => "ext_{$ext}",
            'label'  => "Extension: {$ext}",
            'pass'   => $pass,
            'detail' => $pass ? "Đã cài" : "Chưa cài extension {$ext}",
        ];
    }

    /** @return array{key: string, label: string, pass: bool, detail: string} */
    private function checkWritable(string $key, string $path, string $label): array
    {
        $pass = is_writable($path);

        return [
            'key'    => 'writable_' . str_replace(['/', '-'], '_', rtrim($key, '/')),
            'label'  => "{$label} có thể ghi",
            'pass'   => $pass,
            'detail' => $pass ? "Có thể ghi" : "Không thể ghi vào {$label}",
        ];
    }

    /** @return array{key: string, label: string, pass: bool, detail: string} */
    private function checkEnvFile(): array
    {
        $pass = file_exists(base_path('.env'));

        return [
            'key'    => 'env_file',
            'label'  => '.env tồn tại',
            'pass'   => $pass,
            'detail' => $pass ? "File .env đã tồn tại" : "File .env không tìm thấy — copy từ .env.example",
        ];
    }

    /** @return array{key: string, label: string, pass: bool, detail: string} */
    private function checkAppKey(): array
    {
        $key  = config('app.key', '');
        $pass = $key !== '' && $key !== null;

        return [
            'key'    => 'app_key',
            'label'  => 'APP_KEY đã cấu hình',
            'pass'   => $pass,
            'detail' => $pass ? "APP_KEY đã được đặt" : "APP_KEY chưa được đặt — chạy: php artisan key:generate",
        ];
    }

    /** @return array{key: string, label: string, pass: bool, detail: string} */
    private function checkDbConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            return [
                'key'    => 'db_connection',
                'label'  => 'Kết nối database',
                'pass'   => true,
                'detail' => "Kết nối thành công ({$driver})",
            ];
        } catch (\Throwable $e) {
            return [
                'key'    => 'db_connection',
                'label'  => 'Kết nối database',
                'pass'   => false,
                'detail' => "Không kết nối được: " . $e->getMessage(),
            ];
        }
    }
}
