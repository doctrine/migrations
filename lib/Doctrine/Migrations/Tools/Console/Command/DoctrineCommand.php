<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration\ConfigurationFileWithFallback;
use Doctrine\Migrations\Configuration\Connection\ConfigurationFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleLogger;
use Doctrine\Migrations\Tools\Console\Exception\DependenciesNotSatisfied;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function escapeshellarg;
use function is_string;
use function proc_open;
use function str_repeat;
use function strlen;

/**
 * The DoctrineCommand class provides base functionality for the other migrations commands to extend from.
 */
abstract class DoctrineCommand extends Command
{
    /** @var DependencyFactory|null */
    private $dependencyFactory;

    public function __construct(?DependencyFactory $dependencyFactory = null, ?string $name = null)
    {
        parent::__construct($name);
        $this->dependencyFactory = $dependencyFactory;
    }

    protected function configure() : void
    {
        if ($this->dependencyFactory !== null) {
            return;
        }
        $this->addOption(
            'configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a migrations configuration file. <comment>[default: any of migrations.{php,xml,json,yml,yaml}]</comment>'
        );

        $this->addOption(
            'db-configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a database connection configuration file.',
            'migrations-db.php'
        );
    }

    protected function outputHeader(
        OutputInterface $output
    ) : void {
        $name = $this->getDependencyFactory()->getConfiguration()->getName();
        $name = $name ?? 'Doctrine Database Migrations';
        $name = str_repeat(' ', 20) . $name . str_repeat(' ', 20);
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('<question>' . $name . '</question>');
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('');
    }

    protected function initialize(InputInterface $input, OutputInterface $output) : void
    {
        if ($this->dependencyFactory === null) {
            $configurationLoader     = new ConfigurationFileWithFallback(
                is_string($input->getOption('configuration'))
                    ? $input->getOption('configuration')
                    : null
            );
            $connectionLoader        = new ConfigurationFile((string) $input->getOption('db-configuration'));
            $this->dependencyFactory = DependencyFactory::fromConnection($configurationLoader, $connectionLoader);
        }

        if ($this->dependencyFactory->isFrozen()) {
            return;
        }

        $logger = new ConsoleLogger($output);
        $this->dependencyFactory->setService(LoggerInterface::class, $logger);
        $this->dependencyFactory->freeze();
    }

    protected function getDependencyFactory() : DependencyFactory
    {
        if ($this->dependencyFactory === null) {
            throw DependenciesNotSatisfied::new();
        }

        return $this->dependencyFactory;
    }

    protected function askConfirmation(
        string $question,
        InputInterface $input,
        OutputInterface $output
    ) : bool {
        return $this->getHelper('question')->ask(
            $input,
            $output,
            new ConfirmationQuestion($question)
        );
    }

    protected function canExecute(
        string $question,
        InputInterface $input,
        OutputInterface $output
    ) : bool {
        return ! $input->isInteractive() || $this->askConfirmation($question, $input, $output);
    }

    protected function procOpen(string $editorCommand, string $path) : void
    {
        proc_open($editorCommand . ' ' . escapeshellarg($path), [], $pipes);
    }
}
