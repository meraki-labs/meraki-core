<?php

namespace Meraki\Core\Modules;

use Meraki\Core\Exceptions\CircularDependencyException;
use Meraki\Core\Exceptions\MissingDependencyException;

class DependencyResolver
{
    /**
     * Resolve boot order using Kahn's Algorithm (BFS topological sort).
     *
     * @return string[] Nodes in safe boot order (dependencies first).
     * @throws CircularDependencyException
     * @throws MissingDependencyException
     */
    public function resolve(DependencyGraph $graph): array
    {
        $nodes = $graph->nodes();

        // Validate all declared dependencies exist as registered nodes
        foreach ($nodes as $node) {
            foreach ($graph->dependenciesOf($node) as $dep) {
                if (!in_array($dep, $nodes, true)) {
                    throw new MissingDependencyException($dep, $node);
                }
            }
        }

        // Calculate in-degree for each node
        $inDegree = array_fill_keys($nodes, 0);
        foreach ($nodes as $node) {
            foreach ($graph->dependenciesOf($node) as $dep) {
                // $node depends on $dep → $node's in-degree increases
                $inDegree[$node]++;
            }
        }

        // Seed queue with nodes that have no dependencies
        $queue = [];
        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $queue[] = $node;
            }
        }

        $sorted = [];
        while (!empty($queue)) {
            $current = array_shift($queue);
            $sorted[] = $current;

            // Find nodes that depend on $current and reduce their in-degree
            foreach ($nodes as $node) {
                if (in_array($current, $graph->dependenciesOf($node), true)) {
                    $inDegree[$node]--;
                    if ($inDegree[$node] === 0) {
                        $queue[] = $node;
                    }
                }
            }
        }

        if (count($sorted) < count($nodes)) {
            // Nodes remaining with in-degree > 0 are part of the cycle
            $cycle = array_values(array_filter(
                $nodes,
                fn ($n) => $inDegree[$n] > 0
            ));
            throw new CircularDependencyException($cycle);
        }

        return $sorted;
    }
}
