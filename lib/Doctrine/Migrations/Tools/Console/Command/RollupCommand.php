<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

/**
 * The RollupCommand class is responsible for deleting all previously executed migrations from the versions table
 * and marking the freshly dumped schema migration (that was created with DumpSchemaCommand) as migrated.
 */
final class RollupCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:rollup';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setAliases(['rollup'])
            ->setDescription('Rollup migrations by deleting all tracked versions and insert the one version that exists.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command rolls up migrations by deleting all tracked versions and
inserts the one version that exists that was created with the <info>migrations:dump-schema</info> command.

    <info>%command.full_name%</info>

To dump your schema to a migration version you can use the <info>migrations:dump-schema</info> command.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $question = 'WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)';

        if (! $this->canExecute($question, $input, $output)) {
            $output->writeln('<error>Migration cancelled!</error>');

            return 3;
        }

        $this->getDependencyFactory()->getMetadataStorage()->ensureInitialized();
        $version = $this->getDependencyFactory()->getRollup()->rollup();

        $output->writeln(sprintf(
            'Rolled up migrations to version <info>%s</info>',
            (string) $version
        ));

        return 0;
    }
}
