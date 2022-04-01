<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * The LatestCommand class is responsible for outputting what your latest version is.
 */
final class LatestCommand extends DoctrineCommand
{
    /** @var string|null */
    protected static $defaultName = 'migrations:latest';

    protected function configure(): void
    {
        $this
            ->setAliases(['latest'])
            ->setDescription('Outputs the latest version');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $aliasResolver = $this->getDependencyFactory()->getVersionAliasResolver();

        try {
            $version            = $aliasResolver->resolveVersionAlias('latest');
            $availableMigration = $this->getDependencyFactory()->getMigrationRepository()->getMigration($version);
            $description        = $availableMigration->getMigration()->getDescription();
        } catch (NoMigrationsToExecute $e) {
            $version     = '0';
            $description = '';
        }

        $this->io->text(sprintf(
            "<info>%s</info>%s\n",
            $version,
            $description !== '' ? ' - ' . $description : ''
        ));

        return 0;
    }
}
