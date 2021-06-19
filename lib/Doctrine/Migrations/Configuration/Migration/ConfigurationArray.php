<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Closure;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\BooleanStringFormatter;

use function assert;
use function call_user_func;
use function is_array;
use function is_bool;
use function is_callable;

final class ConfigurationArray implements ConfigurationLoader
{
    /** @var array<string,mixed> */
    private $configurations;

    /**
     * @param array<string,mixed> $configurations
     */
    public function __construct(array $configurations)
    {
        $this->configurations = $configurations;
    }

    public function getConfiguration(): Configuration
    {
        $configMap = [
            'migrations_paths' => static function ($paths, Configuration $configuration): void {
                foreach ($paths as $namespace => $path) {
                    $configuration->addMigrationsDirectory($namespace, $path);
                }
            },
            'migrations' => static function ($migrations, Configuration $configuration): void {
                foreach ($migrations as $className) {
                    $configuration->addMigrationClass($className);
                }
            },

            'connection' => 'setConnectionName',
            'em' => 'setEntityManagerName',

            'table_storage' => [
                'table_name' => 'setTableName',
                'version_column_name' => 'setVersionColumnName',
                'version_column_length' => static function ($value, TableMetadataStorageConfiguration $configuration): void {
                    $configuration->setVersionColumnLength((int) $value);
                },
                'executed_at_column_name' => 'setExecutedAtColumnName',
                'execution_time_column_name' => 'setExecutionTimeColumnName',
            ],

            'organize_migrations' => 'setMigrationOrganization',
            'custom_template' => 'setCustomTemplate',
            'all_or_nothing' => static function ($value, Configuration $configuration): void {
                $configuration->setAllOrNothing(is_bool($value) ? $value : BooleanStringFormatter::toBoolean($value, false));
            },
            'transactional' => static function ($value, Configuration $configuration): void {
                $configuration->setAllOrNothing(is_bool($value) ? $value : BooleanStringFormatter::toBoolean($value, true));
            },
            'check_database_platform' =>  static function ($value, Configuration $configuration): void {
                $configuration->setCheckDatabasePlatform(is_bool($value) ? $value : BooleanStringFormatter::toBoolean($value, false));
            },
        ];

        $object = new Configuration();
        self::applyConfigs($configMap, $object, $this->configurations);

        if ($object->getMetadataStorageConfiguration() === null) {
            $object->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        }

        return $object;
    }

    /**
     * @param mixed[]                                         $configMap
     * @param Configuration|TableMetadataStorageConfiguration $object
     * @param array<string|int,mixed>                         $data
     */
    private static function applyConfigs(array $configMap, $object, array $data): void
    {
        foreach ($data as $configurationKey => $configurationValue) {
            if (! isset($configMap[$configurationKey])) {
                throw InvalidConfigurationKey::new((string) $configurationKey);
            }

            if (is_array($configMap[$configurationKey])) {
                if ($configurationKey !== 'table_storage') {
                    throw InvalidConfigurationKey::new((string) $configurationKey);
                }

                $storageConfig = new TableMetadataStorageConfiguration();
                assert($object instanceof Configuration);
                $object->setMetadataStorageConfiguration($storageConfig);
                self::applyConfigs($configMap[$configurationKey], $storageConfig, $configurationValue);
            } else {
                $callable = $configMap[$configurationKey] instanceof Closure
                    ? $configMap[$configurationKey]
                    : [$object, $configMap[$configurationKey]];
                assert(is_callable($callable));
                call_user_func(
                    $callable,
                    $configurationValue,
                    $object,
                    $data
                );
            }
        }
    }
}
