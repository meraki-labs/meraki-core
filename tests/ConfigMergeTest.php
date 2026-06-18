<?php

namespace Meraki\Core\Tests;

use Meraki\Core\Testing\MerakiTestCase;

class ConfigMergeTest extends MerakiTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('meraki.capabilities.permission.driver', 'custom');
    }

    public function test_deep_merge_preserves_unoverridden_nested_keys(): void
    {
        $this->assertEquals('auto', config('meraki.capabilities.auth.driver'));
        $this->assertEquals('custom', config('meraki.capabilities.permission.driver'));
    }

    public function test_deep_merge_preserves_other_top_level_keys(): void
    {
        $this->assertTrue(config('meraki.enabled'));
        $this->assertNotNull(config('meraki.state_file'));
    }
}
