<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\ArrayConfiguration;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\JsonConfiguration;
use Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class ConfigurationHelper
 * @package Doctrine\DBAL\Migrations\Tools\Console\Helper
 * @internal
 */
class ConfigurationHelper extends Helper implements ConfigurationHelperInterface
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Connection $connection = null, Configuration $configuration = null)
    {
        $this->connection    = $connection;
        $this->configuration = $configuration;
    }

    public function getMigrationConfig(InputInterface $input, OutputWriter $outputWriter)
    {
        /**
         * If a configuration option is passed to the command line, use that configuration
         * instead of any other one.
         */
        if ($input->getOption('configuration')) {
            $outputWriter->write("Loading configuration from command option: " . $input->getOption('configuration'));

            return $this->loadConfig($input->getOption('configuration'), $outputWriter);
        }

        /**
         * If a configuration has already been set using DI or a Setter use it.
         */
        if ($this->configuration) {
            $outputWriter->write("Loading configuration from the integration code of your framework (setter).");

            $this->configuration->setOutputWriter($outputWriter);

            return $this->configuration;
        }

        /**
         * If no any other config has been found, look for default config file in the path.
         */
        $defaultConfig = [
            'migrations.xml',
            'migrations.yml',
            'migrations.yaml',
            'migrations.json',
            'migrations.php',
        ];
        foreach ($defaultConfig as $config) {
            if ($this->configExists($config)) {
                $outputWriter->write("Loading configuration from file: $config");

                return $this->loadConfig($config, $outputWriter);
            }
        }

        return new Configuration($this->connection, $outputWriter);
    }


    private function configExists($config)
    {
        return file_exists($config);
    }

    private function loadConfig($config, OutputWriter $outputWriter)
    {
        $map = [
            'xml'   => XmlConfiguration::class,
            'yaml'  => YamlConfiguration::class,
            'yml'   => YamlConfiguration::class,
            'php'   => ArrayConfiguration::class,
            'json'  => JsonConfiguration::class,
        ];

        $info = pathinfo($config);
        // check we can support this file type
        if (empty($map[$info['extension']])) {
            throw new \InvalidArgumentException('Given config file type is not supported');
        }

        $class         = $map[$info['extension']];
        $configuration = new $class($this->connection, $outputWriter);
        $configuration->load($config);

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'configuration';
    }
}
