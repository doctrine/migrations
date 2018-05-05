<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\Finder\MigrationFinder;
use Exception;
use function get_class;
use function sprintf;

class MigrationException extends Exception
{
    public static function migrationsNamespaceRequired() : self
    {
        return new self(
            'Migrations namespace must be configured in order to use Doctrine migrations.',
            2
        );
    }

    public static function migrationsDirectoryRequired() : self
    {
        return new self(
            'Migrations directory must be configured in order to use Doctrine migrations.',
            3
        );
    }

    public static function noMigrationsToExecute() : self
    {
        return new self(
            'Could not find any migrations to execute.',
            4
        );
    }

    public static function unknownMigrationVersion(string $version) : self
    {
        return new self(
            sprintf(
                'Could not find migration version %s',
                $version
            ),
            5
        );
    }

    public static function alreadyAtVersion(string $version) : self
    {
        return new self(
            sprintf(
                'Database is already at version %s',
                $version
            ),
            6
        );
    }

    public static function duplicateMigrationVersion(
        string $version,
        string $class
    ) : self {
        return new self(
            sprintf(
                'Migration version %s already registered with class %s',
                $version,
                $class
            ),
            7
        );
    }

    public static function configurationFileAlreadyLoaded() : self
    {
        return new self(
            sprintf(
                'Migrations configuration file already loaded'
            ),
            8
        );
    }

    public static function yamlConfigurationNotAvailable() : self
    {
        return new self(
            'Unable to load yaml configuration files, please run `composer require symfony/yaml` to load yaml configuration files.'
        );
    }

    public static function configurationIncompatibleWithFinder(
        string $configurationParameterName,
        MigrationFinder $finder
    ) : self {
        return new self(
            sprintf(
                'Configuration-parameter "%s" cannot be used with finder of type "%s"',
                $configurationParameterName,
                get_class($finder)
            ),
            9
        );
    }

    public static function configurationNotValid(string $message) : self
    {
        return new self($message, 10);
    }

    public static function migrationClassNotFound(
        string $migrationClass,
        ?string $migrationNamespace
    ) : self {
        return new self(
            sprintf(
                'Migration class "%s" was not found. Is it placed in "%s" namespace?',
                $migrationClass,
                $migrationNamespace
            )
        );
    }

    public static function migrationNotConvertibleToSql(
        string $migrationClass
    ) : self {
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
