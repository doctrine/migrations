<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use Doctrine\Migrations\MigrationException;
use Symfony\Component\Yaml\Yaml;
use function class_exists;
use function file_get_contents;
use function is_array;

class YamlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad(string $file) : void
    {
        if (! class_exists(Yaml::class)) {
            throw MigrationException::yamlConfigurationNotAvailable();
        }

        $config = Yaml::parse(file_get_contents($file));

        if (! is_array($config)) {
            throw MigrationException::configurationNotValid('Configuration is not valid YAML.');
        }

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile(
                $file,
                $config['migrations_directory']
            );
        }

        $this->setConfiguration($config);
    }
}
