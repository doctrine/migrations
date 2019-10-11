<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\ConfigurationLoader;
use Doctrine\Migrations\Configuration\Exception\UnknownLoader;
use Doctrine\Migrations\Tools\Console\Exception\FileTypeNotSupported;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use const PATHINFO_EXTENSION;
use function file_exists;
use function is_string;
use function pathinfo;

/**
 * The ConfigurationHelper class is responsible for getting the Configuration instance from one of the supported methods
 * for defining the configuration for your migrations.
 */
final class ConfigurationHelper extends Helper implements ConfigurationHelperInterface
{
    /** @var ConfigurationLoader */
    private $loader;

    public function __construct(?ConfigurationLoader $loader = null)
    {
        $this->loader = $loader ?: new ConfigurationLoader();
    }

    public function getConfiguration(InputInterface $input) : Configuration
    {
        /**
         * If a configuration option is passed to the command line, use that configuration
         * instead of any other one.
         */
        $configurationFile = $input->getOption('configuration');

        if ($configurationFile !== null && is_string($configurationFile)) {
            return $this->loadConfig($configurationFile);
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

        return $this->loader->getLoader('array')->load([]);
    }

    private function configExists(string $config) : bool
    {
        return file_exists($config);
    }

    /**
     * @throws FileTypeNotSupported
     */
    private function loadConfig(string $configFile) : Configuration
    {
        $extension = pathinfo($configFile, PATHINFO_EXTENSION);

        try {
            return $this->loader->getLoader($extension)->load($configFile);
        } catch (UnknownLoader $e) {
            throw FileTypeNotSupported::new();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return 'configuration';
    }
}
