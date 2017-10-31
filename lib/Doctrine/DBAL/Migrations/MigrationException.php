<?php

namespace Doctrine\DBAL\Migrations;

use \Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;

/**
 * Class for Migrations specific exceptions
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MigrationException extends \Exception
{
    public static function migrationsNamespaceRequired()
    {
        return new self('Migrations namespace must be configured in order to use Doctrine migrations.', 2);
    }

    public static function migrationsDirectoryRequired()
    {
        return new self('Migrations directory must be configured in order to use Doctrine migrations.', 3);
    }

    public static function noMigrationsToExecute()
    {
        return new self('Could not find any migrations to execute.', 4);
    }

    public static function unknownMigrationVersion($version)
    {
        return new self(sprintf('Could not find migration version %s', $version), 5);
    }

    public static function alreadyAtVersion($version)
    {
        return new self(sprintf('Database is already at version %s', $version), 6);
    }

    public static function duplicateMigrationVersion($version, $class)
    {
        return new self(sprintf('Migration version %s already registered with class %s', $version, $class), 7);
    }

    public static function configurationFileAlreadyLoaded()
    {
        return new self(sprintf('Migrations configuration file already loaded'), 8);
    }

    public static function configurationIncompatibleWithFinder(
        $configurationParameterName,
        MigrationFinderInterface $finder
    ) {
        return new self(
            sprintf(
                'Configuration-parameter "%s" cannot be used with finder of type "%s"',
                $configurationParameterName,
                get_class($finder)
            ),
            9
        );
    }

    public static function configurationNotValid($msg)
    {
        return new self($msg, 10);
    }

    /**
     * @param string $migrationClass
     * @param string $migrationNamespace
     * @return MigrationException
     */
    public static function migrationClassNotFound($migrationClass, $migrationNamespace)
    {
        return new self(
            sprintf(
                'Migration class "%s" was not found. Is it placed in "%s" namespace?',
                $migrationClass,
                $migrationNamespace
            )
        );
    }

    /**
     * @param string $migrationClass
     * @return MigrationException
     */
    public static function migrationNotConvertibleToSql($migrationClass)
    {
        return new self(
            sprintf(
                'Migration class "%s" contains a prepared statement.
                Unfortunately there is no cross platform way of outputing it as an sql string.
                Do you want to write a PR for it ?',
                $migrationClass
            )
        );
    }
}
