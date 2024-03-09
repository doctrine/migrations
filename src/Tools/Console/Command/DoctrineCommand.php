<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Connection\ConfigurationFile;
use Doctrine\Migrations\Configuration\Migration\ConfigurationFileWithFallback;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleLogger;
use Doctrine\Migrations\Tools\Console\Exception\DependenciesNotSatisfied;
use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_keys;
use function assert;
use function count;
use function is_string;
use function key;
use function sprintf;

/**
 * The DoctrineCommand class provides base functionality for the other migrations commands to extend from.
 */
abstract class DoctrineCommand extends Command
{
    /** @var StyleInterface */
    protected $io;

    public function __construct(
        private DependencyFactory|null $dependencyFactory = null,
        string|null $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addOption(
            'configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a migrations configuration file. <comment>[default: any of migrations.{php,xml,json,yml,yaml}]</comment>',
        );

        $this->addOption(
            'em',
            null,
            InputOption::VALUE_REQUIRED,
            'The name of the entity manager to use.',
        );

        $this->addOption(
            'conn',
            null,
            InputOption::VALUE_REQUIRED,
            'The name of the connection to use.',
        );

        if ($this->dependencyFactory !== null) {
            return;
        }

        $this->addOption(
            'db-configuration',
            null,
            InputOption::VALUE_REQUIRED,
            'The path to a database connection configuration file.',
            'migrations-db.php',
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $configurationParameter = $input->getOption('configuration');
        if ($this->dependencyFactory === null) {
            $configurationLoader     = new ConfigurationFileWithFallback(
                is_string($configurationParameter)
                    ? $configurationParameter
                    : null,
            );
            $connectionLoader        = new ConfigurationFile($input->getOption('db-configuration'));
            $this->dependencyFactory = DependencyFactory::fromConnection($configurationLoader, $connectionLoader);
        } elseif (is_string($configurationParameter)) {
            $configurationLoader = new ConfigurationFileWithFallback($configurationParameter);
            $this->dependencyFactory->setConfigurationLoader($configurationLoader);
        }

        $dependencyFactory = $this->dependencyFactory;

        $this->setNamedEmOrConnection($input);

        if ($dependencyFactory->isFrozen()) {
            return;
        }

        $logger = new ConsoleLogger($output);
        $dependencyFactory->setService(LoggerInterface::class, $logger);
        $dependencyFactory->freeze();
    }

    protected function getDependencyFactory(): DependencyFactory
    {
        if ($this->dependencyFactory === null) {
            throw DependenciesNotSatisfied::new();
        }

        return $this->dependencyFactory;
    }

    protected function canExecute(string $question, InputInterface $input): bool
    {
        return ! $input->isInteractive() || $this->io->confirm($question);
    }

    private function setNamedEmOrConnection(InputInterface $input): void
    {
        assert($this->dependencyFactory !== null);
        $emName   = $input->getOption('em');
        $connName = $input->getOption('conn');
        if ($emName !== null && $connName !== null) {
            throw new InvalidOptionUsage('You can specify only one of the --em and --conn options.');
        }

        if ($this->dependencyFactory->hasEntityManager() && $emName !== null) {
            $this->dependencyFactory->getConfiguration()->setEntityManagerName($emName);

            return;
        }

        if ($connName !== null) {
            $this->dependencyFactory->getConfiguration()->setConnectionName($connName);

            return;
        }
    }

    final protected function getNamespace(InputInterface $input, OutputInterface $output): string
    {
        $configuration = $this->getDependencyFactory()->getConfiguration();

        $namespace = $input->getOption('namespace');
        if ($namespace === '') {
            $namespace = null;
        }

        $dirs = $configuration->getMigrationDirectories();
        if ($namespace === null && count($dirs) === 1) {
            $namespace = key($dirs);
        } elseif ($namespace === null && count($dirs) > 1) {
            $helper    = $this->getHelper('question');
            $question  = new ChoiceQuestion(
                'Please choose a namespace (defaults to the first one)',
                array_keys($dirs),
                0,
            );
            $namespace = $helper->ask($input, $output, $question);
            $this->io->text(sprintf('You have selected the "%s" namespace', $namespace));
        }

        if (! isset($dirs[$namespace])) {
            throw new Exception(sprintf('Path not defined for the namespace "%s"', $namespace));
        }

        assert(is_string($namespace));

        return $namespace;
    }
}
