<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

class Executor
{
    /**
     * @var Purger
     */
    private $purger;

    /**
     * Executor constructor.
     * @param Purger $purger
     */
    public function __construct(Purger $purger)
    {
        $this->purger = $purger;
    }

    public function execute(array $fixtures, $append = false, bool $onlyPurge = false): void
    {
        if ($append === false) {
            /** @var FixtureInterface $fixture */
            foreach ($fixtures as $fixture) {
                $prefix = '';

                if ($fixture instanceof OrderedFixtureInterface) {
                    $prefix = sprintf('[%d] ', $fixture->getOrder());
                }

                Logger::green('Purging ' . $prefix . get_class($fixture));

                $this->purger->purgeFixture($fixture);
            }
        }

        if ($onlyPurge) {
            Logger::green('Only purging');

            return;
        }

        /** @var FixtureInterface $fixture */
        foreach ($fixtures as $fixture) {
            $prefix = '';

            if ($fixture instanceof OrderedFixtureInterface) {
                $prefix = sprintf('[%d] ', $fixture->getOrder());
            }

            Logger::green('Loading ' . $prefix . get_class($fixture));

            $fixture->load();
        }
    }
}
