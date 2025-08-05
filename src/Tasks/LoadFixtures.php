<?php

declare(strict_types=1);

namespace App\Fixtures\Tasks;

use AdrHumphreys\Fixtures\Executor;
use AdrHumphreys\Fixtures\Loader;
use AdrHumphreys\Fixtures\Logger;
use AdrHumphreys\Fixtures\Purger;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class LoadFixtures extends BuildTask
{
    protected static string $commandName = 'load-fixtures';

    protected string $title = 'Load fixtures';

    protected static string $description = 'This will first clear the loaded fixtures then reload them';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        if (Director::isLive()) {
            if ($input->getOption('force') !== 'true') {
                Logger::log('This is not designed to be run on production');

                return Command::INVALID;
            }

            Logger::orange('This is not designed to be run on production');
            Logger::log('But you have forced it to be run in production');
        }

        $directory = $input->getOption('directory');

        $append = $input->getOption('append') === 'true';

        $onlyPurge = $input->getOption('onlyPurge') === 'true';

        $filter = $input->getOption('filter');

        if ($append && $onlyPurge) {
            Logger::log("You've asked for nothing...");

            return Command::INVALID;
        }

        if (!$directory) {
            Logger::orange('No directory passed through', false, true);

            return Command::FAILURE;
        }

        $loader = new Loader();
        $loader->loadFromDirectory($directory, $filter);
        $purger = new Purger();
        $executor = new Executor($purger);
        $executor->execute($loader->getFixtures(), $append, $onlyPurge);

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('force', null, InputOption::VALUE_OPTIONAL, 'Force run in production?', 'false'),
            new InputOption('directory', null, InputOption::VALUE_REQUIRED, 'Directory to search for fixtures.', ''),
            new InputOption('append', null, InputOption::VALUE_OPTIONAL, 'Append to existing data?', 'false'),
            new InputOption('onlyPurge', null, InputOption::VALUE_OPTIONAL, 'Only purge data?', 'false'),
            new InputOption('filter', null, InputOption::VALUE_OPTIONAL, 'Force run in production?'),
        ];
    }
}
