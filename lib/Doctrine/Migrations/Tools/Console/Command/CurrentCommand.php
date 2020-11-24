<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * The CurrentCommand class is responsible for outputting what your current version is.
 */
final class CurrentCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:current';

    protected function configure(): void
    {
        $this
            ->setAliases(['current'])
            ->setDescription('Outputs the current version');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $aliasResolver = $this->getDependencyFactory()->getVersionAliasResolver();

        $version = $aliasResolver->resolveVersionAlias('current');
        if ((string) $version === '0') {
            $description = '(No migration executed yet)';
        } else {
            try {
                $availableMigration = $this->getDependencyFactory()->getMigrationRepository()->getMigration($version);
                $description        = $availableMigration->getMigration()->getDescription();
            } catch (MigrationClassNotFound $e) {
                $description = '(Migration info not available)';
            }
        }

        $this->io->text(sprintf(
            "<info>%s</info>%s\n",
            (string) $version,
            $description !== '' ? ' - ' . $description : ''
        ));

        return 0;
    }
}
