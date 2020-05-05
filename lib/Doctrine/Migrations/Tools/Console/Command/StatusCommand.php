<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The StatusCommand class is responsible for outputting what the current state is of all your migrations. It shows
 * what your current version is, how many new versions you have to execute, etc. and details about each of your migrations.
 */
final class StatusCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:status';

    protected function configure() : void
    {
        $this
            ->setAliases(['status'])
            ->setDescription('View the status of a set of migrations.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs the status of a set of migrations:

    <info>%command.full_name%</info>
EOT
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $infosHelper = $this->getDependencyFactory()->getMigrationStatusInfosHelper();
        $infosHelper->showMigrationsInfo($output);

        return 0;
    }
}
