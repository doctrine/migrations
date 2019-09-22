<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Closure;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Configuration\Exception\UnknownResource;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\BooleanStringFormatter;
use function call_user_func;
use function is_array;
use function is_bool;

/**
 * The ArrayConfiguration class is responsible for loading migration configuration information from a PHP file.
 *
 * @internal
 */
class ArrayLoader implements Loader
{
    /**
     * @param mixed $array
     */
    public function load($array) : Configuration
    {
        if (! is_array($array)) {
            throw UnknownResource::new(static::class);
        }

        $configMap = [
            'migrations_paths' => static function ($paths, Configuration $configuration) : void {
                foreach ($paths as $namespace => $path) {
                    $configuration->addMigrationsDirectory($namespace, $path);
                }
            },
            'table_storage' => [
                'table_name' => 'setTableName',
                'version_column_name' => 'setVersionColumnName',
                'version_column_length' => static function ($value, TableMetadataStorageConfiguration $configuration) : void {
                    $configuration->setVersionColumnLength((int) $value);
                },
                'executed_at_column_name' => 'setExecutedAtColumnName',
                'execution_time_column_name' => 'setExecutionTimeColumnName',
            ],

            'organize_migrations' => 'setMigrationOrganization',
            'name' => 'setName',
            'custom_template' => 'setCustomTemplate',
            'all_or_nothing' => static function ($value, Configuration $configuration) : void {
                $configuration->setAllOrNothing(is_bool($value) ? $value : BooleanStringFormatter::toBoolean($value, false));
            },
            'check_database_platform' =>  static function ($value, Configuration $configuration) : void {
                $configuration->setCheckDatabasePlatform(is_bool($value) ? $value :BooleanStringFormatter::toBoolean($value, false));
            },
        ];

        $object = new Configuration();
        self::applyConfigs($configMap, $object, $array);

        return $object;
    }

    /**
     * @param mixed[]                                         $configMap
     * @param Configuration|TableMetadataStorageConfiguration $object
     * @param mixed[]                                         $data
     */
    private static function applyConfigs(array $configMap, $object, array $data) : void
    {
        foreach ($data as $configurationKey => $configurationValue) {
            if (! isset($configMap[$configurationKey])) {
                throw InvalidConfigurationKey::new($configurationKey);
            }

            if (is_array($configMap[$configurationKey])) {
                if ($configurationKey === 'table_storage') {
                    $storageConfig = new TableMetadataStorageConfiguration();
                    $object->setMetadataStorageConfiguration($storageConfig);
                    self::applyConfigs($configMap[$configurationKey], $storageConfig, $configurationValue);
                }
            } else {
                call_user_func(
                    $configMap[$configurationKey] instanceof Closure ? $configMap[$configurationKey]  : [$object, $configMap[$configurationKey]],
                    $configurationValue,
                    $object,
                    $data
                );
            }
        }
    }
}
