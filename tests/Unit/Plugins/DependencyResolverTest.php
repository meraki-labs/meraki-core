<?php

namespace Meraki\Core\Tests\Unit\Plugins;

use PHPUnit\Framework\TestCase;
use Meraki\Core\Plugins\AbstractPlugin;
use Meraki\Core\Plugins\DependencyResolver;
use Illuminate\Contracts\Foundation\Application;

class DependencyResolverTest extends TestCase
{
    private function makePlugin(string $id, array $deps = []): \Meraki\Core\Contracts\Plugin
    {
        return new class($id, $deps) extends AbstractPlugin {
            public function __construct(private string $pluginId, private array $pluginDeps) {}
            public function id(): string          { return $this->pluginId; }
            public function name(): string        { return $this->pluginId; }
            public function version(): string     { return '1.0.0'; }
            public function dependencies(): array { return $this->pluginDeps; }
        };
    }

    private function makeResolver(array $plugins): DependencyResolver
    {
        $keyed = [];
        foreach ($plugins as $p) {
            $keyed[$p->id()] = $p;
        }
        return new DependencyResolver($keyed);
    }

    // 1. resolveActivationOrder: chain A→B→C returns [A, B, C]
    public function test_resolve_activation_order_respects_dependency_chain(): void
    {
        $a = $this->makePlugin('a');
        $b = $this->makePlugin('b', ['a']);
        $c = $this->makePlugin('c', ['b']);

        $resolver = $this->makeResolver([$a, $b, $c]);
        $order    = $resolver->resolveActivationOrder(['a', 'b', 'c']);

        $this->assertSame(['a', 'b', 'c'], $order);
    }

    // 2. canActivate true khi deps đủ
    public function test_can_activate_returns_true_when_deps_satisfied(): void
    {
        $a = $this->makePlugin('a');
        $b = $this->makePlugin('b', ['a']);

        $resolver = $this->makeResolver([$a, $b]);

        $this->assertTrue($resolver->canActivate('b', ['a']));
    }

    // 3. canActivate false khi dep thiếu
    public function test_can_activate_returns_false_when_dep_missing(): void
    {
        $a = $this->makePlugin('a');
        $b = $this->makePlugin('b', ['a']);

        $resolver = $this->makeResolver([$a, $b]);

        $this->assertFalse($resolver->canActivate('b', []));
    }

    // 4. missingDeps trả về đúng array
    public function test_missing_deps_returns_unsatisfied_dependencies(): void
    {
        $a = $this->makePlugin('a');
        $b = $this->makePlugin('b');
        $c = $this->makePlugin('c', ['a', 'b']);

        $resolver = $this->makeResolver([$a, $b, $c]);
        $missing  = $resolver->missingDeps('c', ['a']);

        $this->assertSame(['b'], $missing);
    }

    // 5. Circular dependency throws RuntimeException
    public function test_resolve_activation_order_throws_on_circular_dependency(): void
    {
        $a = $this->makePlugin('a', ['b']);
        $b = $this->makePlugin('b', ['a']);

        $resolver = $this->makeResolver([$a, $b]);

        $this->expectException(\RuntimeException::class);
        $resolver->resolveActivationOrder(['a', 'b']);
    }

    // 6. canDeactivate false khi còn active dependent
    public function test_can_deactivate_returns_false_when_active_dependent_exists(): void
    {
        $a = $this->makePlugin('a');
        $b = $this->makePlugin('b', ['a']);

        $resolver = $this->makeResolver([$a, $b]);

        $this->assertFalse($resolver->canDeactivate('a', ['a', 'b']));
    }

    // 7. canDeactivate true khi không còn dependent
    public function test_can_deactivate_returns_true_when_no_active_dependents(): void
    {
        $a = $this->makePlugin('a');
        $b = $this->makePlugin('b', ['a']);

        $resolver = $this->makeResolver([$a, $b]);

        // b is not active, so a can be deactivated
        $this->assertTrue($resolver->canDeactivate('a', ['a']));
    }
}
