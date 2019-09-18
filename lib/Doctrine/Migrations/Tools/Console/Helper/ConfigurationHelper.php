<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Loader\JsonFileLoader;
use Doctrine\Migrations\Configuration\Loader\PHPFileLoader;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;
use Doctrine\Migrations\Configuration\Loader\YamlFileLoader;
use Doctrine\Migrations\Tools\Console\Exception\FileTypeNotSupported;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use function file_exists;
use function pathinfo;

/**
 * The ConfigurationHelper class is responsible for getting the Configuration instance from one of the supported methods
 * for defining the configuration for your migrations.
 */
class ConfigurationHelper extends Helper implements ConfigurationHelperInterface
{
    /** @var Configuration|null */
    private $configuration;

    public function __construct(
        ?Configuration $configuration = null
    ) {
        $this->configuration = $configuration;
    }

    public function getMigrationConfig(InputInterface $input) : Configuration
    {
        /**
         * If a configuration option is passed to the command line, use that configuration
         * instead of any other one.
         */
        $configuration = $input->getOption('configuration');

        if ($configuration !== null) {
            return $this->loadConfig($configuration);
        }

        /**
         * If a configuration has already been set using DI or a Setter use it.
         */
        if ($this->configuration !== null) {
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
                return $this->loadConfig($config);
            }
        }

        return new Configuration();
    }

    private function configExists(string $config) : bool
    {
        return file_exists($config);
    }

    /**
     * @throws FileTypeNotSupported
     */
    private function loadConfig(string $config) : Configuration
    {
        $map = [
            'xml'   => new XmlFileLoader(),
            'yaml'  => new YamlFileLoader(),
            'yml'   => new YamlFileLoader(),
            'php'   => new PHPFileLoader(),
            'json'  => new JsonFileLoader(),
        ];

        $info = pathinfo($config);

        // check we can support this file type
        if (! isset($map[$info['extension']])) {
            throw FileTypeNotSupported::new();
        }

        $loader = $map[$info['extension']];

        return $loader->load($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return 'configuration';
    }
}
