<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Doctrine\Migrations\Tools\Console\Exception\SchemaDumpRequiresNoMigrations;
use Doctrine\SqlFormatter\SqlFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function addslashes;
use function class_exists;
use function sprintf;
use function str_contains;

/**
 * The DumpSchemaCommand class is responsible for dumping your current database schema to a migration class. This is
 * intended to be used in conjunction with the RollupCommand.
 *
 * @see Doctrine\Migrations\Tools\Console\Command\RollupCommand
 */
#[AsCommand(name: 'migrations:dump-schema', description: 'Dump the schema for your database to a migration.')]
final class DumpSchemaCommand extends DoctrineCommand
{
    /** @var string|null */
    protected static $defaultName = 'migrations:dump-schema';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setAliases(['dump-schema'])
            ->setDescription('Dump the schema for your database to a migration.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command dumps the schema for your database to a migration:

    <info>%command.full_name%</info>

After dumping your schema to a migration, you can rollup your migrations using the <info>migrations:rollup</info> command.
EOT)
            ->addOption(
                'formatted',
                null,
                InputOption::VALUE_NONE,
                'Format the generated SQL.',
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'Namespace to use for the generated migrations (defaults to the first namespace definition).',
            )
            ->addOption(
                'filter-tables',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Filter the tables to dump via Regex.',
            )
            ->addOption(
                'line-length',
                null,
                InputOption::VALUE_OPTIONAL,
                'Max line length of unformatted lines.',
                '120',
            );
    }

    /** @throws SchemaDumpRequiresNoMigrations */
    public function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $formatted  = $input->getOption('formatted');
        $lineLength = (int) $input->getOption('line-length');

        $schemaDumper = $this->getDependencyFactory()->getSchemaDumper();

        if ($formatted) {
            if (! class_exists(SqlFormatter::class)) {
                throw InvalidOptionUsage::new(
                    'The "--formatted" option can only be used if the sql formatter is installed. Please run "composer require doctrine/sql-formatter".',
                );
            }
        }

        $namespace = $this->getNamespace($input, $output);

        $this->checkNoPreviousDumpExistsForNamespace($namespace);

        $fqcn = $this->getDependencyFactory()->getClassNameGenerator()->generateClassName($namespace);

        $path = $schemaDumper->dump(
            $fqcn,
            $input->getOption('filter-tables'),
            $formatted,
            $lineLength,
        );

        $this->io->text([
            sprintf('Dumped your schema to a new migration class at "<info>%s</info>"', $path),
            '',
            sprintf(
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up \'%s\'</info>',
                addslashes($fqcn),
            ),
            '',
            sprintf(
                'To revert the migration you can use <info>migrations:execute --down \'%s\'</info>',
                addslashes($fqcn),
            ),
            '',
            'To use this as a rollup migration you can use the <info>migrations:rollup</info> command.',
            '',
        ]);

        return 0;
    }

    private function checkNoPreviousDumpExistsForNamespace(string $namespace): void
    {
        $migrations = $this->getDependencyFactory()->getMigrationRepository()->getMigrations();
        foreach ($migrations->getItems() as $migration) {
            if (str_contains((string) $migration->getVersion(), $namespace)) {
                throw SchemaDumpRequiresNoMigrations::new($namespace);
            }
        }
    }
}
