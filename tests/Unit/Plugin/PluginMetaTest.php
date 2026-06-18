<?php

namespace Meraki\Core\Tests\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Meraki\Core\Plugin\PluginMeta;

class PluginMetaTest extends TestCase
{
    public function test_creates_with_all_fields(): void
    {
        $meta = new PluginMeta(
            name:        'meraki-auth',
            version:     '1.0.0',
            description: 'JWT-based auth',
            config:      'meraki-auth',
            requires:    ['meraki-core'],
        );

        $this->assertSame('meraki-auth', $meta->name);
        $this->assertSame('1.0.0', $meta->version);
        $this->assertSame('JWT-based auth', $meta->description);
        $this->assertSame('meraki-auth', $meta->config);
        $this->assertSame(['meraki-core'], $meta->requires);
    }

    public function test_creates_with_only_required_fields(): void
    {
        $meta = new PluginMeta(name: 'meraki-cms', version: '2.0.0');

        $this->assertSame('meraki-cms', $meta->name);
        $this->assertSame('2.0.0', $meta->version);
        $this->assertSame('', $meta->description);
        $this->assertNull($meta->config);
        $this->assertSame([], $meta->requires);
    }

    public function test_properties_are_readonly(): void
    {
        $meta = new PluginMeta(name: 'meraki-cms', version: '1.0.0');

        $this->expectException(\Error::class);
        $meta->name = 'changed'; // @phpstan-ignore-line
    }
}
