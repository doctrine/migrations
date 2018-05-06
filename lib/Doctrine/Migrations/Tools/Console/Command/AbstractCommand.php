<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionConfigurationChainLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelperInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function str_repeat;
use function strlen;

abstract class AbstractCommand extends Command
{
    /** @var Configuration */
    private $configuration;

    /** @var Configuration|null */
    private $migrationConfiguration;

    /** @var OutputWriter|null */
    private $outputWriter;

    /** @var Connection|null */
    private $connection;

    protected function configure() : void
    {
        $this->addOption(
            'configuration',
            null,
            InputOption::VALUE_OPTIONAL,
            'The path to a migrations configuration file.'
        );

        $this->addOption(
            'db-configuration',
            null,
            InputOption::VALUE_OPTIONAL,
            'The path to a database connection configuration file.'
        );
    }

    protected function outputHeader(
        Configuration $configuration,
        OutputInterface $output
    ) : void {
        $name = $configuration->getName();
        $name = $name ?? 'Doctrine Database Migrations';
        $name = str_repeat(' ', 20) . $name . str_repeat(' ', 20);
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('<question>' . $name . '</question>');
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('');
    }

    public function setMigrationConfiguration(Configuration $config) : void
    {
        $this->configuration = $config;
    }

    protected function getMigrationConfiguration(
        InputInterface $input,
        OutputInterface $output
    ) : Configuration {
        if ($this->migrationConfiguration === null) {
            if ($this->hasConfigurationHelper()) {
                /** @var ConfigurationHelper $configHelper */
                $configHelper = $this->getHelperSet()->get('configuration');
            } else {
                $configHelper = new ConfigurationHelper(
                    $this->getConnection($input),
                    $this->configuration
                );
            }

            $this->migrationConfiguration = $configHelper->getMigrationConfig(
                $input,
                $this->getOutputWriter($output)
            );
        }

        return $this->migrationConfiguration;
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

    private function hasConfigurationHelper() : bool
    {
        if (! $this->getHelperSet()->has('configuration')) {
            return false;
        }

        return $this->getHelperSet()->get('configuration') instanceof ConfigurationHelperInterface;
    }

    private function getOutputWriter(OutputInterface $output) : OutputWriter
    {
        if ($this->outputWriter === null) {
            $this->outputWriter = new OutputWriter(
                function (string $message) use ($output) : void {
                    $output->writeln($message);
                }
            );
        }

        return $this->outputWriter;
    }

    private function getConnection(InputInterface $input) : Connection
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $chainLoader = new ConnectionConfigurationChainLoader(
            [
                new ArrayConnectionConfigurationLoader($input->getOption('db-configuration')),
                new ArrayConnectionConfigurationLoader('migrations-db.php'),
                new ConnectionHelperLoader($this->getHelperSet(), 'connection'),
                new ConnectionConfigurationLoader($this->configuration),
            ]
        );

        $connection = $chainLoader->chosen();

        if ($connection !== null) {
            return $this->connection = $connection;
        }

        throw new InvalidArgumentException(
            'You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.'
        );
    }
}
