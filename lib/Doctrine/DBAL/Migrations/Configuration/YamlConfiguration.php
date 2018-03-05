<?php

namespace Doctrine\DBAL\Migrations\Configuration;

use Doctrine\DBAL\Migrations\MigrationException;
use Symfony\Component\Yaml\Yaml;

/**
 * Load migration configuration information from a YAML configuration file.
 */
class YamlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        if (! class_exists(Yaml::class)) {
            throw MigrationException::yamlConfigurationNotAvailable();
        }

        $config = Yaml::parse(file_get_contents($file));

        if (! is_array($config)) {
            throw new \InvalidArgumentException('Not valid configuration.');
        }

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile($file, $config['migrations_directory']);
        }

        $this->setConfiguration($config);
    }
}
