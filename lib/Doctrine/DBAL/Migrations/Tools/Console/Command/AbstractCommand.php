<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\DBAL\Migrations\Configuration\Connection\Loader\ConnectionConfigurationLoader;
use Doctrine\DBAL\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\DBAL\Migrations\Configuration\Connection\Loader\ConnectionConfigurationChainLoader;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CLI Command for adding and deleting migration versions from the version table.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    /**
     * The configuration property only contains the configuration injected by the setter.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The migrationConfiguration property contains the configuration
     * created taking into account the command line options.
     *
     * @var Configuration
     */
    private $migrationConfiguration;

    /**
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    protected function configure()
    {
        $this->addOption('configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a migrations configuration file.');
        $this->addOption('db-configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a database connection configuration file.');
    }

    protected function outputHeader(Configuration $configuration, OutputInterface $output)
    {
        $name = $configuration->getName();
        $name = $name ? $name : 'Doctrine Database Migrations';
        $name = str_repeat(' ', 20) . $name . str_repeat(' ', 20);
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('<question>' . $name . '</question>');
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('');
    }

    public function setMigrationConfiguration(Configuration $config)
    {
        $this->configuration = $config;
    }

    /**
     * When any (config) command line option is passed to the migration the migrationConfiguration
     * property is set with the new generated configuration.
     * If no (config) option is passed the migrationConfiguration property is set to the value
     * of the configuration one (if any).
     * Else a new configuration is created and assigned to the migrationConfiguration property.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return Configuration
     */
    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output)
    {
        if ( ! $this->migrationConfiguration) {
            if ($this->getHelperSet()->has('configuration')
                && $this->getHelperSet()->get('configuration') instanceof ConfigurationHelperInterface) {
                $configHelper = $this->getHelperSet()->get('configuration');
            } else {
                $configHelper = new ConfigurationHelper($this->getConnection($input), $this->configuration);
            }
            $this->migrationConfiguration = $configHelper->getMigrationConfig($input, $this->getOutputWriter($output));
        }

        return $this->migrationConfiguration;
    }

    /**
     * @param string $question
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function askConfirmation($question, InputInterface $input, OutputInterface $output)
    {
        return $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion($question));
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Doctrine\DBAL\Migrations\OutputWriter
     */
    private function getOutputWriter(OutputInterface $output)
    {
        if ( ! $this->outputWriter) {
            $this->outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });
        }

        return $this->outputWriter;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getConnection(InputInterface $input)
    {
        if ($this->connection) {
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
        $connection  = $chainLoader->chosen();

        if ($connection) {
            return $this->connection = $connection;
        }

        throw new \InvalidArgumentException('You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.');
    }
}
