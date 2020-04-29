<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Connection\ConfigurationFile;
use Doctrine\Migrations\Configuration\Migration\ConfigurationFileWithFallback;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleLogger;
use Doctrine\Migrations\Tools\Console\Exception\DependenciesNotSatisfied;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function is_string;

/**
 * The DoctrineCommand class provides base functionality for the other migrations commands to extend from.
 */
abstract class DoctrineCommand extends Command
{
    /** @var DependencyFactory|null */
    private $dependencyFactory;

    /** @var StyleInterface */
    protected $io;

    public function __construct(?DependencyFactory $dependencyFactory = null, ?string $name = null)
    {
        parent::__construct($name);
        $this->dependencyFactory = $dependencyFactory;
    }

    protected function configure() : void
    {
        $this->addOption(
            'configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a migrations configuration file. <comment>[default: any of migrations.{php,xml,json,yml,yaml}]</comment>'
        );

        if ($this->dependencyFactory !== null) {
            return;
        }

        $this->addOption(
            'db-configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a database connection configuration file.',
            'migrations-db.php'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output) : void
    {
        $this->io = new SymfonyStyle($input, $output);

        $configurationParameter = $input->getOption('configuration');
        if ($this->dependencyFactory === null) {
            $configurationLoader     = new ConfigurationFileWithFallback(
                is_string($configurationParameter)
                    ? $configurationParameter
                    : null
            );
            $connectionLoader        = new ConfigurationFile((string) $input->getOption('db-configuration'));
            $this->dependencyFactory = DependencyFactory::fromConnection($configurationLoader, $connectionLoader);
        } elseif (is_string($configurationParameter)) {
            $configurationLoader = new ConfigurationFileWithFallback($configurationParameter);
            $this->dependencyFactory->setConfigurationLoader($configurationLoader);
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

    protected function canExecute(string $question, InputInterface $input) : bool
    {
        return ! $input->isInteractive() || $this->io->confirm($question);
    }
}
