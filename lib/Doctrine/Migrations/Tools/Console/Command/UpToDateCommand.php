<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function count;
use function sprintf;

class UpToDateCommand extends AbstractCommand
{
    protected function configure() : void
    {
        $this
            ->setName('migrations:up-to-date')
            ->setDescription('Tells you if your schema is up-to-date.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command tells you if your schema is up-to-date:

    <info>%command.full_name%</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $migrations          = count($configuration->getMigrations());
        $migratedVersions    = count($configuration->getMigratedVersions());
        $availableMigrations = $migrations - $migratedVersions;

        if ($availableMigrations === 0) {
            $output->writeln('<comment>Up-to-date! No migrations to execute.</comment>');

            return 0;
        }

        $output->writeln(sprintf(
            '<comment>Out-of-date! %u migration%s available to execute.</comment>',
            $availableMigrations,
            $availableMigrations > 1 ? 's are' : ' is'
        ));

        return 1;
    }
}
