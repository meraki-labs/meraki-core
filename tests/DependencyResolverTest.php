<?php

namespace Meraki\Core\Tests;

use PHPUnit\Framework\TestCase;
use Meraki\Core\Modules\DependencyGraph;
use Meraki\Core\Modules\DependencyResolver;
use Meraki\Core\Exceptions\CircularDependencyException;
use Meraki\Core\Exceptions\MissingDependencyException;

class DependencyResolverTest extends TestCase
{
    private DependencyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DependencyResolver();
    }

    public function test_empty_graph_returns_empty_array(): void
    {
        $graph = new DependencyGraph();
        $result = $this->resolver->resolve($graph);
        $this->assertSame([], $result);
    }

    public function test_graph_with_no_edges_returns_all_nodes(): void
    {
        $graph = new DependencyGraph();
        $graph->addNode('meraki-hub');
        $graph->addNode('meraki-cms');
        $graph->addNode('meraki-crm');

        $result = $this->resolver->resolve($graph);

        $this->assertCount(3, $result);
        $this->assertContains('meraki-hub', $result);
        $this->assertContains('meraki-cms', $result);
        $this->assertContains('meraki-crm', $result);
    }

    public function test_a_depends_on_b_returns_b_before_a(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('a', 'b'); // a depends on b

        $result = $this->resolver->resolve($graph);

        $this->assertSame(['b', 'a'], $result);
    }

    public function test_chain_a_b_c_returns_c_b_a(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('a', 'b'); // a depends on b
        $graph->addEdge('b', 'c'); // b depends on c

        $result = $this->resolver->resolve($graph);

        $this->assertSame(['c', 'b', 'a'], $result);
    }

    public function test_diamond_dependency_b_and_c_before_a(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('a', 'b'); // a depends on b
        $graph->addEdge('a', 'c'); // a depends on c

        $result = $this->resolver->resolve($graph);

        $this->assertCount(3, $result);
        $this->assertSame('a', end($result)); // a must come last
        $this->assertContains('b', $result);
        $this->assertContains('c', $result);
    }

    public function test_cycle_throws_circular_dependency_exception(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('a', 'b'); // a depends on b
        $graph->addEdge('b', 'a'); // b depends on a → cycle

        $this->expectException(CircularDependencyException::class);
        $this->resolver->resolve($graph);
    }

    public function test_missing_dependency_throws_missing_dependency_exception(): void
    {
        $graph = new DependencyGraph();
        $graph->addNode('a');
        // Manually add edge to an unregistered node
        $graph->addEdge('a', 'b-unregistered');
        // Remove b-unregistered from nodes by reconstructing without it
        $graph2 = new DependencyGraph();
        $graph2->addNode('a');
        // Simulate: a declares requires b but b is NOT registered
        // We do this by overriding via a stub graph
        $stubGraph = new class extends DependencyGraph {
            public function nodes(): array { return ['a']; }
            public function dependenciesOf(string $name): array {
                return $name === 'a' ? ['b-missing'] : [];
            }
        };

        $this->expectException(MissingDependencyException::class);
        $this->resolver->resolve($stubGraph);
    }

    public function test_missing_dependency_exception_carries_metadata(): void
    {
        $stubGraph = new class extends DependencyGraph {
            public function nodes(): array { return ['meraki-cms']; }
            public function dependenciesOf(string $name): array {
                return $name === 'meraki-cms' ? ['meraki-hub'] : [];
            }
        };

        try {
            $this->resolver->resolve($stubGraph);
            $this->fail('Expected MissingDependencyException');
        } catch (MissingDependencyException $e) {
            $this->assertSame('meraki-hub', $e->missing);
            $this->assertSame('meraki-cms', $e->requiredBy);
        }
    }

    public function test_circular_dependency_exception_message_contains_cycle(): void
    {
        $graph = new DependencyGraph();
        $graph->addEdge('a', 'b');
        $graph->addEdge('b', 'a');

        try {
            $this->resolver->resolve($graph);
            $this->fail('Expected CircularDependencyException');
        } catch (CircularDependencyException $e) {
            $this->assertStringContainsString('→', $e->getMessage());
        }
    }
}
