<?php

namespace Doctrine\DBAL\Migrations\Configuration;

use Doctrine\DBAL\Migrations\MigrationException;

/**
 * Abstract Migration Configuration class for loading configuration information
 * from a configuration file (xml or yml).
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractFileConfiguration extends Configuration
{
    /**
     * The configuration file used to load configuration information
     *
     * @var string
     */
    private $file;

    /**
     * Whether or not the configuration file has been loaded yet or not
     *
     * @var boolean
     */
    private $loaded = false;

    /**
     * @var array of possible configuration properties in migrations configuration.
     */
    private $configurationProperties = [
        'migrations_namespace' => 'setMigrationsNamespace',
        'table_name' => 'setMigrationsTableName',
        'column_name' => 'setMigrationsColumnName',
        'organize_migrations' => 'setMigrationOrganisation',
        'name' => 'setName',
        'migrations_directory' => 'loadMigrationsFromDirectory',
        'migrations' => 'loadMigrations',
        'custom_template' => 'setCustomTemplate',
    ];

    protected function setConfiguration(array $config)
    {
        foreach ($config as $configurationKey => $configurationValue) {
            if ( ! isset($this->configurationProperties[$configurationKey])) {
                $msg = sprintf('Migrations configuration key "%s" does not exist.', $configurationKey);
                throw MigrationException::configurationNotValid($msg);
            }
        }
        foreach ($this->configurationProperties as $configurationKey => $configurationSetter) {
            if (isset($config[$configurationKey])) {
                $this->{$configurationSetter}($config[$configurationKey]);
            }
        }
    }

    private function loadMigrationsFromDirectory($migrationsDirectory)
    {
        $this->setMigrationsDirectory($migrationsDirectory);
        $this->registerMigrationsFromDirectory($migrationsDirectory);
    }

    private function loadMigrations($migrations)
    {
        if (is_array($migrations)) {
            foreach ($migrations as $migration) {
                $this->registerMigration($migration['version'], $migration['class']);
            }
        }
    }

    private function setMigrationOrganisation($migrationOrganisation)
    {
        if (strcasecmp($migrationOrganisation, static::VERSIONS_ORGANIZATION_BY_YEAR) == 0) {
            $this->setMigrationsAreOrganizedByYear();
        } elseif (strcasecmp($migrationOrganisation, static::VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH) == 0) {
            $this->setMigrationsAreOrganizedByYearAndMonth();
        } else {
            $msg = 'Unknown ' . var_export($migrationOrganisation, true) . ' for configuration "organize_migrations".';
            throw MigrationException::configurationNotValid($msg);
        }
    }

    /**
     * Load the information from the passed configuration file
     *
     * @param string $file The path to the configuration file
     *
     * @throws MigrationException Throws exception if configuration file was already loaded
     */
    public function load($file)
    {
        if ($this->loaded) {
            throw MigrationException::configurationFileAlreadyLoaded();
        }
        if (file_exists($path = getcwd() . '/' . $file)) {
            $file = $path;
        }
        $this->file = $file;

        if ( ! file_exists($file)) {
            throw new \InvalidArgumentException('Given config file does not exist');
        }

        $this->doLoad($file);
        $this->loaded = true;
    }

    protected function getDirectoryRelativeToFile($file, $input)
    {
        $path = realpath(dirname($file) . '/' . $input);

        return ($path !== false) ? $path : $input;
    }

    public function getFile()
    {
        return $this->file;
    }

    /**
     * Abstract method that each file configuration driver must implement to
     * load the given configuration file whether it be xml, yaml, etc. or something
     * else.
     *
     * @param string $file The path to a configuration file.
     */
    abstract protected function doLoad($file);
}
