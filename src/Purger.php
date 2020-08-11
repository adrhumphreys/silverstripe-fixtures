<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\Versioned\Versioned;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\Extension\FluentVersionedExtension;

class Purger
{
    use Configurable;

    /**
     * @config
     *
     * Table names to be excluded from purge
     *
     * @var string[]
     */
    private static $excludedTables = [];

    /**
     * @var string[] - Used internally to not truncate multiple tables multiple times
     */
    private $clearedTables = [];

    /**
     * @var string[] - Used internally to not clear classes multiple times
     */
    private $clearedClasses = [];

    public function purgeFixture(FixtureInterface $fixture): void
    {
        $fixture->unload();

        $classes = $fixture->getClassesToClear();

        if (is_array($classes)) {
            foreach ($classes as $class) {
                $this->deleteClass($class);
            }
        }

        $tables = $fixture->getTablesToClear();

        if (is_array($tables)) {
            foreach ($tables as $table) {
                $this->truncateTable($table);
            }
        }
    }

    /*
     * Delete all the associated tables for a class
     */
    private function deleteClass(string $class): void
    {
        if (array_key_exists($class, $this->clearedClasses)) {
            Logger::blue("$class already cleared");

            return;
        }

        // First delete the base classes
        $tableClasses = ClassInfo::ancestry($class, true);
        foreach ($tableClasses as $tableClass) {
            $table = DataObject::getSchema()->tableName($tableClass);
            self::truncateTable($table);
        }

        /** @var DataObject|FluentExtension|Versioned $obj */
        $obj = Injector::inst()->get($class);

        $versionedTables = [];
        $hasVersionedExtension = $obj->hasExtension(Versioned::class);

        if ($hasVersionedExtension) {
            $baseTableName = Config::inst()->get($class, 'table_name');
            $stages = $obj->getVersionedStages();

            foreach ($stages as $stage) {
                $table = $obj->stageTable($baseTableName, $stage);
                self::truncateTable($table);
                $versionedTables[] = $table;
            }
        }

        if ($obj->hasExtension(FluentExtension::class)) {
            // Fluent passes back `['table_name' => ['arrayOfLocalisedFields']]`
            $localisedTables = array_keys($obj->getLocalisedTables());

            foreach ($localisedTables as $localisedTable) {
                $table = $obj->getLocalisedTable($localisedTable);
                self::truncateTable($table);

                if ($hasVersionedExtension) {
                    self::truncateTable($table . FluentVersionedExtension::SUFFIX_VERSIONS);
                }
            }

            if ($hasVersionedExtension) {
                foreach ($versionedTables as $versionedTable) {
                    $table = $obj->getLocalisedTable($versionedTable);
                    self::truncateTable($table);
                }
            }
        }

        $this->clearedClasses[$class] = true;
    }

    /*
     * Attempts to truncate a table if it hasn't already been truncated
     */
    private function truncateTable(string $table): void
    {
        if (array_key_exists($table, $this->clearedTables)) {
            Logger::blue("$table already truncated");

            return;
        }

        $excludedTables = self::config()->get('excludedTables');

        if (in_array($table, $excludedTables)) {
            Logger::blue("$table not truncated as it's excluded");

            return;
        }

        Logger::blue("Truncating table $table");

        // Error page will try and create the record again when it's been deleted
        // it first finds the "existing" instance in "Error page" and then tries up
        // write an update to it. This causes a unique key mysql error.
        // To resolve this we just don't delete the error pages original SiteTree
        // record, so a draft of it always exists
        if ($table === SiteTree::config()->get('table_name')
            && class_exists(ErrorPage::class)) {

            SQLDelete::create(
                [$table],
                ['ClassName != ? ' => ErrorPage::class],
                [$table]
            )->execute();

            Logger::blue("Truncated all of $table aside from ErrorPages");

            $this->clearedTables[$table] = true;

            return;
        }

        try {
            DB::get_conn()->clearTable($table);
        } catch (DatabaseException $databaseException) {
            Logger::blue("Couldn't truncate table $table as it doesn't exist");
        }

        $this->clearedTables[$table] = true;
    }
}
