<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

/*
 * Quality of life improvements for implementing fixtures as
 * you'll often only need one of the methods here. We also
 * log that their not implemented to prompt developers to be aware
 * of their existence
 */
abstract class AbstractFixture implements FixtureInterface
{
    public function unload(): void
    {
//        Logger::orange("`unload` not implemented for " . get_class($this));
    }

    public function getClassesToClear(): ?array
    {
//        Logger::orange("`getClassesToClear` not implemented for " . get_class($this));

        return null;
    }

    public function getTablesToClear(): ?array
    {
//        Logger::orange("`getTablesToClear` not implemented for " . get_class($this));

        return null;
    }

    public function addReference(string $identifier, object $reference): void
    {
        ReferenceManager::addReference($identifier, $reference);
    }

    public function getByReference(string $identifier): ?object
    {
        return ReferenceManager::getByReference($identifier);
    }
}
