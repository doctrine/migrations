<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Generator\Exception\NoChangesDetected;
use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use const FILTER_VALIDATE_BOOLEAN;
use function class_exists;
use function filter_var;
use function sprintf;

/**
 * The DiffCommand class is responsible for generating a migration by comparing your current database schema to
 * your mapping information.
 */
class DiffCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:diff';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setAliases(['diff'])
            ->setDescription('Generate a migration by comparing your current database to your mapping information.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a migration by comparing your current database to your mapping information:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
            )
            ->addOption(
                'editor-cmd',
                null,
                InputOption::VALUE_OPTIONAL,
                'Open file with this command upon creation.'
            )
            ->addOption(
                'filter-expression',
                null,
                InputOption::VALUE_OPTIONAL,
                'Tables which are filtered by Regular Expression.'
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
                120
            )
            ->addOption(
                'check-database-platform',
                null,
                InputOption::VALUE_OPTIONAL,
                'Check Database Platform to the generated code.',
                true
            )
            ->addOption(
                'allow-empty-diff',
                null,
                InputOption::VALUE_NONE,
                'Do not throw an exception when no changes are detected.'
            );
    }

    /**
     * @throws InvalidOptionUsage
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) : ?int {
        $filterExpression = $input->getOption('filter-expression') ?? null;
        $formatted        = (bool) $input->getOption('formatted');
        $lineLength       = (int) $input->getOption('line-length');
        $allowEmptyDiff   = (bool) $input->getOption('allow-empty-diff');
        $checkDbPlatform  = filter_var($input->getOption('check-database-platform'), FILTER_VALIDATE_BOOLEAN);

        if ($formatted) {
            if (! class_exists('SqlFormatter')) {
                throw InvalidOptionUsage::new(
                    'The "--formatted" option can only be used if the sql formatter is installed. Please run "composer require jdorn/sql-formatter".'
                );
            }
        }

        $versionNumber = $this->getDependencyFactory()->getConfiguration()->generateVersionNumber();

        $diffGenerator = $this->getDependencyFactory()->getDiffGenerator();

        try {
            $path = $diffGenerator->generate(
                $versionNumber,
                $filterExpression,
                $formatted,
                $lineLength,
                $checkDbPlatform
            );
        } catch (NoChangesDetected $exception) {
            if ($allowEmptyDiff) {
                $output->writeln($exception->getMessage());

                return 0;
            }
            throw $exception;
        }

        $editorCommand = $input->getOption('editor-cmd');

        if ($editorCommand !== null) {
            $this->procOpen($editorCommand, $path);
        }

        $output->writeln([
            sprintf('Generated new migration class to "<info>%s</info>"', $path),
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
        ]);

        return 0;
    }
}
