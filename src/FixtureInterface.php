<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

/**
 * Interface contract for fixture classes to implement.
 */
interface FixtureInterface
{
    /**
     * Load data fixtures
     */
    public function load(): void;

    /**
     * Optionally run code to remove the items
     */
    public function unload(): void;

    /**
     * Optionally return an array of classes to purge
     */
    public function getClassesToClear(): ?array;

    /**
     * Optionally return an array of tables to purge
     */
    public function getTablesToClear(): ?array;
}
