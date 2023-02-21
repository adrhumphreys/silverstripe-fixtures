<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

use Exception;

class KahnSorter
{
    /**
     * @var array[]
     */
    private array $nodes = [];

    /**
     * Example input:
     * [
     *  ['node' => 1, 'dependencies' => [2,3,4]],
     *  ['node' => 1, 'dependencies' => [1]],
     *  ['node' => 1, 'dependencies' => null],
     * ]
     * @param array[] $nodes
     */
    public function __construct(array $nodes)
    {
        foreach ($nodes as $node) {
            $this->nodes[$node['name']] = [
                'name' => $node['name'],
                'dependencies' => $node['dependencies'] ?? [],
                'count' => 0,
            ];

            // We do this since we want to support unspecified dependencies
            foreach ($node['dependencies'] as $dependency) {
                // A more informed version of it has been set
                if (isset($this->nodes[$dependency])) {
                    continue;
                }

                $this->nodes[$dependency] = [
                    'name' => $dependency,
                    'dependencies' => [],
                    'count' => 0,
                ];
            }
        }

        foreach ($this->nodes as $node) {
            $name = $node['name'];
            $edges = [];

            if (is_array($node['dependencies'])) {
                foreach ($node['dependencies'] as $edge) {
                    $edges[] = $edge;
                }
            }

            Logger::orange(sprintf(
                '[Dependency resolution] %s depends on [%s]',
                $name,
                implode(',', $edges)
            ));
        }
    }

    public function sort(): array
    {
        $pending = [];

        foreach ($this->nodes as $node) {
            foreach ($node['dependencies'] as $dependency) {
                $this->nodes[$dependency]['count'] += 1;
            }
        }

        foreach ($this->nodes as $node) {
            if ($node['count'] === 0) {
                $pending[] = $node;
            }
        }

        $output = [];

        while (count($pending) > 0) {
            $currentNode = array_pop($pending);
            $output[] = $currentNode['name'];

            if (is_array($currentNode['dependencies'])) {
                foreach ($currentNode['dependencies'] as $dependency) {
                    $this->nodes[$dependency]['count'] -= 1;
                    if ($this->nodes[$dependency]['count'] === 0) {
                        $pending[] = $this->nodes[$dependency];
                    }
                }
            }
        }

        foreach ($this->nodes as $node) {
            if ($node['count'] !== 0) {
                Logger::orange(sprintf(
                    'Node `%s` has `%s` left over dependencies',
                    $node['name'],
                    $node['count']
                ));
            }
        }

        return array_reverse($output);
    }
}
