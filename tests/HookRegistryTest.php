<?php

namespace Meraki\Core\Tests;

use PHPUnit\Framework\TestCase;
use Meraki\Core\Hooks\HookRegistry;

class HookRegistryTest extends TestCase
{
    private HookRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new HookRegistry();
    }

    public function test_add_and_fire_calls_callback(): void
    {
        $called = false;
        $this->registry->add('test.hook', function () use (&$called) {
            $called = true;
        });

        $this->registry->fire('test.hook');

        $this->assertTrue($called);
    }

    public function test_fire_passes_args_to_callback(): void
    {
        $received = [];
        $this->registry->add('test.hook', function (...$args) use (&$received) {
            $received = $args;
        });

        $this->registry->fire('test.hook', 'foo', 42);

        $this->assertSame(['foo', 42], $received);
    }

    public function test_callbacks_run_in_priority_order(): void
    {
        $order = [];

        $this->registry->add('test.hook', function () use (&$order) { $order[] = 'second'; }, 20);
        $this->registry->add('test.hook', function () use (&$order) { $order[] = 'first'; }, 5);
        $this->registry->add('test.hook', function () use (&$order) { $order[] = 'default'; });

        $this->registry->fire('test.hook');

        $this->assertSame(['first', 'default', 'second'], $order);
    }

    public function test_fire_with_no_registered_callbacks_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        $this->registry->fire('nonexistent.hook');
    }

    public function test_remove_unregisters_callback(): void
    {
        $calls = 0;
        $cb = function () use (&$calls) { $calls++; };

        $this->registry->add('test.hook', $cb);
        $this->registry->remove('test.hook', $cb);
        $this->registry->fire('test.hook');

        $this->assertSame(0, $calls);
    }

    public function test_remove_only_removes_target_callback(): void
    {
        $calls = [];
        $cbA = function () use (&$calls) { $calls[] = 'A'; };
        $cbB = function () use (&$calls) { $calls[] = 'B'; };

        $this->registry->add('test.hook', $cbA);
        $this->registry->add('test.hook', $cbB);
        $this->registry->remove('test.hook', $cbA);
        $this->registry->fire('test.hook');

        $this->assertSame(['B'], $calls);
    }

    public function test_remove_nonexistent_hook_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        $cb = function () {};
        $this->registry->remove('nonexistent.hook', $cb);
    }

    public function test_has_returns_true_when_callbacks_registered(): void
    {
        $this->registry->add('test.hook', function () {});
        $this->assertTrue($this->registry->has('test.hook'));
    }

    public function test_has_returns_false_when_no_callbacks(): void
    {
        $this->assertFalse($this->registry->has('test.hook'));
    }

    public function test_has_returns_false_after_all_callbacks_removed(): void
    {
        $cb = function () {};
        $this->registry->add('test.hook', $cb);
        $this->registry->remove('test.hook', $cb);
        $this->assertFalse($this->registry->has('test.hook'));
    }

    public function test_multiple_callbacks_same_priority_all_run(): void
    {
        $order = [];
        $this->registry->add('test.hook', function () use (&$order) { $order[] = 1; });
        $this->registry->add('test.hook', function () use (&$order) { $order[] = 2; });
        $this->registry->add('test.hook', function () use (&$order) { $order[] = 3; });

        $this->registry->fire('test.hook');

        $this->assertSame([1, 2, 3], $order);
    }
}
