<?php

namespace Meraki\Core\Plugins;

use Meraki\Core\Contracts\Plugin;

class DependencyResolver
{
    /** @param array<string, Plugin> $plugins keyed by plugin id */
    public function __construct(private array $plugins) {}

    /** True nếu tất cả deps của $id đều có trong $activeIds */
    public function canActivate(string $id, array $activeIds): bool
    {
        return count($this->missingDeps($id, $activeIds)) === 0;
    }

    /** True nếu không có active plugin nào depend vào $id */
    public function canDeactivate(string $id, array $activeIds): bool
    {
        return count($this->dependents($id, $activeIds)) === 0;
    }

    /** Trả về list deps của $id chưa có trong $activeIds */
    public function missingDeps(string $id, array $activeIds): array
    {
        $deps = isset($this->plugins[$id]) ? $this->plugins[$id]->dependencies() : [];
        return array_values(array_diff($deps, $activeIds));
    }

    /** Trả về list active plugins đang depend vào $id */
    public function dependents(string $id, array $activeIds): array
    {
        $result = [];
        foreach ($activeIds as $activeId) {
            if ($activeId === $id) {
                continue;
            }
            $deps = isset($this->plugins[$activeId]) ? $this->plugins[$activeId]->dependencies() : [];
            if (in_array($id, $deps, true)) {
                $result[] = $activeId;
            }
        }
        return $result;
    }

    /**
     * Kahn's topological sort — trả về thứ tự activate.
     * Throws \RuntimeException nếu có circular dependency.
     *
     * @param  string[] $ids
     * @return string[]
     */
    public function resolveActivationOrder(array $ids): array
    {
        $inDegree = array_fill_keys($ids, 0);
        $adj      = array_fill_keys($ids, []);

        foreach ($ids as $id) {
            $deps = isset($this->plugins[$id]) ? $this->plugins[$id]->dependencies() : [];
            foreach ($deps as $dep) {
                if (!array_key_exists($dep, $inDegree)) {
                    continue;
                }
                $adj[$dep][] = $id;
                $inDegree[$id]++;
            }
        }

        $queue  = array_keys(array_filter($inDegree, fn ($d) => $d === 0));
        $sorted = [];

        while (!empty($queue)) {
            $node     = array_shift($queue);
            $sorted[] = $node;
            foreach ($adj[$node] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }

        if (count($sorted) !== count($ids)) {
            $cycle = implode(', ', array_diff($ids, $sorted));
            throw new \RuntimeException("Circular dependency detected among plugins: [{$cycle}]");
        }

        return $sorted;
    }
}
