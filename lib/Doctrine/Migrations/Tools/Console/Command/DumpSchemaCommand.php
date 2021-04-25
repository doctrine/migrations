<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Doctrine\Migrations\Tools\Console\Exception\SchemaDumpRequiresNoMigrations;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function class_exists;
use function count;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;

/**
 * The DumpSchemaCommand class is responsible for dumping your current database schema to a migration class. This is
 * intended to be used in conjuction with the RollupCommand.
 *
 * @see Doctrine\Migrations\Tools\Console\Command\RollupCommand
 */
class DumpSchemaCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:dump-schema';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setAliases(['dump-schema'])
            ->setDescription('Dump the schema for your database to a migration.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command dumps the schema for your database to a migration:

    <info>%command.full_name%</info>

After dumping your schema to a migration, you can rollup your migrations using the <info>migrations:rollup</info> command.
EOT
            )
            ->addOption(
                'editor-cmd',
                null,
                InputOption::VALUE_OPTIONAL,
                'Open file with this command upon creation.'
            )
            ->addOption(
                'formatted',
                null,
                InputOption::VALUE_NONE,
                'Format the generated SQL.'
            )
            ->addOption(
                'line-length',
                null,
                InputOption::VALUE_OPTIONAL,
                'Max line length of unformatted lines.',
                '120'
            );
    }

    /**
     * @throws SchemaDumpRequiresNoMigrations
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): ?int {
        $formatted  = (bool) $input->getOption('formatted');
        $lineLength = $input->getOption('line-length');
        assert(! is_array($lineLength) && ! is_bool($lineLength));
        $lineLength = (int) $lineLength;

        $schemaDumper = $this->dependencyFactory->getSchemaDumper();
        $versions     = $this->migrationRepository->getVersions();

        if (count($versions) > 0) {
            throw SchemaDumpRequiresNoMigrations::new();
        }

        if ($formatted) {
            if (! class_exists('SqlFormatter')) {
                throw InvalidOptionUsage::new(
                    'The "--formatted" option can only be used if the sql formatter is installed. Please run "composer require jdorn/sql-formatter".'
                );
            }
        }

        $versionNumber = $this->configuration->generateVersionNumber();

        $path = $schemaDumper->dump(
            $versionNumber,
            $formatted,
            $lineLength
        );

        $editorCommand = $input->getOption('editor-cmd');
        assert(is_string($editorCommand) || $editorCommand === null);

        if ($editorCommand !== null) {
            $this->procOpen($editorCommand, $path);
        }

        $output->writeln([
            sprintf('Dumped your schema to a new migration class at "<info>%s</info>"', $path),
            '',
            sprintf(
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up %s</info>',
                $versionNumber
            ),
            '',
            sprintf(
                'To revert the migration you can use <info>migrations:execute --down %s</info>',
                $versionNumber
            ),
            '',
            'To use this as a rollup migration you can use the <info>migrations:rollup</info> command.',
        ]);

        return 0;
    }
}
