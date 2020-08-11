<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

use DirectoryIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Interface contract for fixture classes to implement.
 */
class Loader
{
    /**
     * Array of fixture object instances to execute.
     *
     * @var array
     */
    private $fixtures = [];

    /**
     * Array of ordered fixture object instances.
     *
     * @var array
     */
    private $orderedFixtures = [];

    /**
     * Determines if we must order fixtures by number
     *
     * @var bool
     */
    private $orderFixturesByNumber = false;

    /**
     * Determines if we must order fixtures by its dependencies
     *
     * @var bool
     */
    private $orderFixturesByDependencies = false;

    /*
     * Find fixtures classes in a given directory and load them.
     */
    public function loadFromDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('"%s" does not exist', $dir));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $includedFiles = [];

        /** @var DirectoryIterator $file */
        foreach ($iterator as $file) {
            $fileName = $file->getBasename('.php');
            if ($fileName === $file->getBasename()) {
                continue;
            }

            $sourceFile = realpath($file->getPathName());
            require_once $sourceFile;
            $includedFiles[] = $sourceFile;
        }

        $declared = get_declared_classes();
        // Make the declared classes order deterministic
        sort($declared);

        foreach ($declared as $className) {
            $reflClass = new ReflectionClass($className);
            $sourceFile = $reflClass->getFileName();

            if (!in_array($sourceFile, $includedFiles) || $reflClass->isAbstract()) {
                continue;
            }

            $this->addFixture($className);
        }
    }

    public function addFixture(string $fixtureClass): void
    {
        if (!class_exists($fixtureClass)) {
            throw new InvalidArgumentException('No fixture exists for ' . $fixtureClass);
        }

        // Prevent recursive loops
        if (isset($this->fixtures[$fixtureClass])) {
            return;
        }

        $fixture = new $fixtureClass();

        if ($fixture instanceof OrderedFixtureInterface
            && $fixture instanceof DependentFixtureInterface) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" can\'t implement "%s" and "%s" at the same time.',
                $fixtureClass,
                'OrderedFixtureInterface',
                'DependentFixtureInterface'
            ));
        }

        $this->fixtures[$fixtureClass] = $fixture;

        if ($fixture instanceof OrderedFixtureInterface) {
            $this->orderFixturesByNumber = true;
        } elseif ($fixture instanceof DependentFixtureInterface) {
            $this->orderFixturesByDependencies = true;

            foreach ($fixture->getDependencies() as $class) {
                if (!class_exists($class)) {
                    throw new InvalidArgumentException(sprintf(
                        'No fixture exists for %s (dependency of %s)',
                        $class,
                        $fixtureClass
                    ));
                }

                $this->addFixture($class);
            }
        }
    }

    public function getFixtures(): array
    {
        $this->orderedFixtures = [];

        if ($this->orderFixturesByNumber) {
            $this->orderFixturesByNumber();
        }

        if ($this->orderFixturesByDependencies) {
            $this->orderFixturesByDependencies();
        }

        if (!$this->orderFixturesByNumber && !$this->orderFixturesByDependencies) {
            $this->orderedFixtures = $this->fixtures;
        }

        return $this->orderedFixtures;
    }

    private function orderFixturesByNumber(): void
    {
        $this->orderedFixtures = $this->fixtures;

        usort($this->orderedFixtures, static function ($a, $b) {
            if ($a instanceof OrderedFixtureInterface && $b instanceof OrderedFixtureInterface) {
                if ($a->getOrder() === $b->getOrder()) {
                    return 0;
                }

                return $a->getOrder() < $b->getOrder() ? -1 : 1;
            }

            if ($a instanceof OrderedFixtureInterface) {
                return $a->getOrder() === 0 ? 0 : 1;
            }

            if ($b instanceof OrderedFixtureInterface) {
                return $b->getOrder() === 0 ? 0 : -1;
            }

            return 0;
        });
    }

    /**
     * Orders fixtures by dependencies
     *
     * @return void
     */
    private function orderFixturesByDependencies()
    {
        $dependencies = [];

        if (!$this->orderFixturesByNumber) {
            $this->orderedFixtures = $this->fixtures;
        }

        // Remove fixtures that are not dependent on order from the ordered list
        // this prevents us from messing with them when we shouldn't. We also
        // know that they can't have dependencies so we can load them in first
        // as there might be fixtures that depend on them
        foreach ($this->orderedFixtures as $key => $fixture) {
            $fixtureClass = get_class($fixture);

            if (!$fixture instanceof DependentFixtureInterface) {
                continue;
            }

            $dependenciesClasses = $fixture->getDependencies();

            if (in_array($fixtureClass, $dependenciesClasses)) {
                throw new InvalidArgumentException(
                    sprintf('Class "%s" can\'t have itself as a dependency', $fixtureClass)
                );
            }

            unset($this->orderedFixtures[$key]);
            $dependencies[] = [
                'name' => $fixtureClass,
                'dependencies' => $dependenciesClasses,
            ];
        }

        // Now sort the dependencies
        $sorter = new KahnSorter($dependencies);
        $sortedResults = $sorter->sort();

        $orderedFixtures = [];

        foreach ($sortedResults as $class) {
            // There is a chance we've added the dependency to the results
            // that isn't a dependent item so we can bypass that for now
            if (!$this->fixtures[$class] instanceof DependentFixtureInterface) {
                continue;
            }

            $orderedFixtures[] = $this->fixtures[$class];
        }

        $this->orderedFixtures = array_merge($this->orderedFixtures, $orderedFixtures);
    }
}
