<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\Exception\MissingConfigurationFile;
use Doctrine\Migrations\Tools\Console\Exception\FileTypeNotSupported;
use function file_exists;

/**
 * This class creates a configuration instance from a configuration file passed as argument.
 * If no arguments are provided, will try to load one of migrations.{xml, yml, yaml, json, php} files.
 *
 * @internal
 */
final class ConfigurationFileWithFallback implements ConfigurationLoader
{
    /** @var string|null */
    private $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file;
    }

    public function getConfiguration() : Configuration
    {
        if ($this->file !== null) {
            return $this->loadConfiguration($this->file);
        }

        /**
         * If no config has been provided, look for default config file in the path.
         */
        $defaultFiles = [
            'migrations.xml',
            'migrations.yml',
            'migrations.yaml',
            'migrations.json',
            'migrations.php',
        ];

        foreach ($defaultFiles as $file) {
            if ($this->configurationFileExists($file)) {
                return $this->loadConfiguration($file);
            }
        }

        throw MissingConfigurationFile::new();
    }

    private function configurationFileExists(string $config) : bool
    {
        return file_exists($config);
    }

    /**
     * @throws FileTypeNotSupported
     */
    private function loadConfiguration(string $file) : Configuration
    {
        return (new FormattedFile($file))->getConfiguration();
    }
}
