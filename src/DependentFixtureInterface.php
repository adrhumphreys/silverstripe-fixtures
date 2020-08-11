<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

interface DependentFixtureInterface
{
    public function getDependencies(): array;
}
