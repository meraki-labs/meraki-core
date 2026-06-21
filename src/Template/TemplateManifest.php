<?php

namespace Meraki\Core\Template;

class TemplateManifest
{
    private array $managed = [];

    private function __construct(private readonly string $path) {}

    public static function load(): self
    {
        $path = base_path('.meraki/manifest.json');
        $instance = new self($path);

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true) ?? [];
            $instance->managed = $data['managed'] ?? [];
        }

        return $instance;
    }

    public function record(string $relativePath, string $checksum): void
    {
        $this->managed[$relativePath] = [
            'checksum' => $checksum,
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public function getChecksum(string $relativePath): ?string
    {
        return $this->managed[$relativePath]['checksum'] ?? null;
    }

    public function getManagedPaths(): array
    {
        return array_keys($this->managed);
    }

    public function isEmpty(): bool
    {
        return empty($this->managed);
    }

    public function save(): void
    {
        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->path,
            json_encode([
                'generated_at' => now()->toDateTimeString(),
                'managed' => $this->managed,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
