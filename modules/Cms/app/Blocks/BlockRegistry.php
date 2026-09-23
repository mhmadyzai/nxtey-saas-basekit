<?php

namespace Modules\Cms\Blocks;

class BlockRegistry
{
    /** @var array<string, array{label: string, icon: string, schema: callable, view: string}> */
    protected array $blocks = [];

    public function register(string $type, array $definition): void
    {
        $this->blocks[$type] = $definition;
    }

    public function all(): array
    {
        return $this->blocks;
    }

    public function get(string $type): ?array
    {
        return $this->blocks[$type] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->blocks[$type]);
    }
}