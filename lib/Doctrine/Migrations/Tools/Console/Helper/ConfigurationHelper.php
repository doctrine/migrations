<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\ArrayConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\JsonConfiguration;
use Doctrine\Migrations\Configuration\XmlConfiguration;
use Doctrine\Migrations\Configuration\YamlConfiguration;
use Doctrine\Migrations\OutputWriter;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use function file_exists;
use function pathinfo;
use function sprintf;

class ConfigurationHelper extends Helper implements ConfigurationHelperInterface
{
    /** @var Connection */
    private $connection;

    /** @var null|Configuration */
    private $configuration;

    public function __construct(
        Connection $connection,
        ?Configuration $configuration = null
    ) {
        $this->connection    = $connection;
        $this->configuration = $configuration;
    }

    public function getMigrationConfig(InputInterface $input, OutputWriter $outputWriter) : Configuration
    {
        /**
         * If a configuration option is passed to the command line, use that configuration
         * instead of any other one.
         */
        $configuration = $input->getOption('configuration');
        if ($configuration !== null) {
            $outputWriter->write(
                sprintf(
                    'Loading configuration from command option: %s',
                    $configuration
                )
            );

            return $this->loadConfig($configuration, $outputWriter);
        }

        /**
         * If a configuration has already been set using DI or a Setter use it.
         */
        if ($this->configuration !== null) {
            $outputWriter->write(
                'Loading configuration from the integration code of your framework (setter).'
            );

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
                $outputWriter->write(sprintf('Loading configuration from file: %s', $config));

                return $this->loadConfig($config, $outputWriter);
            }
        }

        return new Configuration($this->connection, $outputWriter);
    }


    private function configExists(string $config) : bool
    {
        return file_exists($config);
    }

    /** @throws InvalidArgumentException */
    private function loadConfig(string $config, OutputWriter $outputWriter) : Configuration
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
            throw new InvalidArgumentException('Given config file type is not supported');
        }

        $class         = $map[$info['extension']];
        $configuration = new $class($this->connection, $outputWriter);
        $configuration->load($config);

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return 'configuration';
    }
}
