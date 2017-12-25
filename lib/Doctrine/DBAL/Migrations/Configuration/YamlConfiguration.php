<?php

namespace Doctrine\DBAL\Migrations\Configuration;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Migrations\MigrationException;

/**
 * Load migration configuration information from a YAML configuration file.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class YamlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        if ( ! class_exists(Yaml::class)) {
            throw MigrationException::yamlConfigurationNotAvailable();
        }

        $config = Yaml::parse(file_get_contents($file));

        if ( ! is_array($config)) {
            throw new \InvalidArgumentException('Not valid configuration.');
        }

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile($file, $config['migrations_directory']);
        }

        $this->setConfiguration($config);
    }
}
