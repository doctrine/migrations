<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Closure;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use function assert;
use function call_user_func;
use function is_array;

/**
 * The ArrayConfiguration class is responsible for loading migration configuration information from a PHP file.
 *
 * @internal
 */
class ArrayLoader implements Loader
{
    public function load($array) : Configuration
    {
        assert(is_array($array));
        $configMap = [
            'migrations_paths' => static function ($paths, Configuration $configuration) : void {
                foreach ($paths as $namespace => $path) {
                    $configuration->addMigrationsDirectory($namespace, $path);
                }
            },
            'table_storage' => [
                'table_name' => 'setTableName',
                'version_column_name' => 'setVersionColumnName',
                'version_column_length' => 'setVersionColumnLength',
                'executed_at_column_name' => 'setExecutedAtColumnName',
                'execution_time_column_name' => 'setExecutionTimeColumnName',
            ],

            'organize_migrations' => 'setMigrationOrganization',
            'name' => 'setName',
            'custom_template' => 'setCustomTemplate',
            'all_or_nothing' => 'setAllOrNothing',
            'check_database_platform' =>  'setCheckDatabasePlatform',
        ];

        $object = new Configuration();
        self::applyConfigs($configMap, $object, $array);

        return $object;
    }

    private static function applyConfigs(array $configMap, $object, $data) : void
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
