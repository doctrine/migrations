<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use Doctrine\Migrations\Configuration\Exception\UnknownLoader;
use Doctrine\Migrations\Tools\Console\Exception\FileTypeNotSupported;
use const PATHINFO_EXTENSION;
use function file_exists;
use function pathinfo;

/**
 * The ConfigurationLoader class is responsible for getting the Configuration instance from one of the supported methods
 * for defining the configuration for your migrations.
 */
final class ConfigurationLoader
{
    /** @var ConfigurationFormatLoader */
    private $loader;

    public function __construct(?ConfigurationFormatLoader $loader = null)
    {
        $this->loader = $loader ?: new ConfigurationFormatLoader();
    }

    public function getConfiguration(?string $configurationFile) : Configuration
    {
        if ($configurationFile !== null) {
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
}
