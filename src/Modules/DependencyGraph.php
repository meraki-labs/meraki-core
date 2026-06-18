<?php

namespace Meraki\Core\Modules;

class DependencyGraph
{
    // ['meraki-cms' => ['meraki-hub'], 'meraki-hub' => []]
    protected array $edges = [];

    public function addNode(string $name): void
    {
        if (!array_key_exists($name, $this->edges)) {
            $this->edges[$name] = [];
        }
    }

    /** $from depends on $to */
    public function addEdge(string $from, string $to): void
    {
        $this->addNode($from);
        $this->addNode($to);
        if (!in_array($to, $this->edges[$from], true)) {
            $this->edges[$from][] = $to;
        }
    }

    public function nodes(): array
    {
        return array_keys($this->edges);
    }

    public function dependenciesOf(string $name): array
    {
        return $this->edges[$name] ?? [];
    }
}
