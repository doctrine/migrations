<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function assert;
use function is_string;
use function key;
use function sprintf;

/**
 * The GenerateCommand class is responsible for generating a blank migration class for you to modify to your needs.
 */
class GenerateCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:generate';

    protected function configure() : void
    {
        $this
            ->setAliases(['generate'])
            ->setDescription('Generate a blank migration class.')
            ->addOption(
                'editor-cmd',
                null,
                InputOption::VALUE_OPTIONAL,
                'Open file with this command upon creation.'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'The namespace to use for the migration (must be in the list of configured namespaces)'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        $configuration = $this->getDependencyFactory()->getConfiguration();

        $migrationGenerator = $this->getDependencyFactory()->getMigrationGenerator();

        $namespace = $input->getOption('namespace') ?: null;

        $dirs = $configuration->getMigrationDirectories();
        if ($namespace === null) {
            $namespace = key($dirs);
        } elseif (! isset($dirs[$namespace])) {
            throw new Exception(sprintf('Path not defined for the namespace %s', $namespace));
        }
        assert(is_string($namespace));

        $fqcn = $this->getDependencyFactory()->getClassNameGenerator()->generateClassName($namespace);

        $path = $migrationGenerator->generateMigration($fqcn);

        $editorCommand = $input->getOption('editor-cmd');

        if ($editorCommand !== null) {
            assert(is_string($editorCommand));
            $this->procOpen($editorCommand, $path);
        }

        $output->writeln([
            sprintf('Generated new migration class to "<info>%s</info>"', $path),
            '',
            sprintf(
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up \'%s\'</info>',
                $fqcn
            ),
            '',
            sprintf(
                'To revert the migration you can use <info>migrations:execute --down \'%s\'</info>',
                $fqcn
            ),
        ]);

        return 0;
    }
}
