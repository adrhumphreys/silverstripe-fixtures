<?php

declare(strict_types=1);

namespace App\Fixtures\Tasks;

use AdrHumphreys\Fixtures\Executor;
use AdrHumphreys\Fixtures\Loader;
use AdrHumphreys\Fixtures\Logger;
use AdrHumphreys\Fixtures\Purger;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;

class LoadFixtures extends BuildTask
{
    private static $segment = 'load-fixtures';

    protected $title = 'Load fixtures';

    protected $description = 'This will first clear the loaded fixtures then reload them';

    /**
     * @param HTTPRequest|mixed $request
     */
    public function run($request): void
    {
        if (Director::isLive()) {
            if ($request->getVar('force') !== 'true') {
                Logger::log('This is not designed to be run on production');

                return;
            }

            Logger::orange('This is not designed to be run on production');
            Logger::log('But you have forced it to be run in production');
        }

        $directory = $request->getVar('directory');

        $append = $request->getVar('append') === 'true';

        $onlyPurge = $request->getVar('onlyPurge') === 'true';

        $filter = $request->getVar('filter') ?? null;

        if ($append && $onlyPurge) {
            Logger::log("You've asked for nothing...");

            return;
        }

        if (!$directory) {
            Logger::orange('No directory passed through', false, true);

            return;
        }

        $loader = new Loader();
        $loader->loadFromDirectory($directory, $filter);
        $purger = new Purger();
        $executor = new Executor($purger);
        $executor->execute($loader->getFixtures(), $append, $onlyPurge);
    }
}
