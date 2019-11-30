<?php

declare(strict_types=1);

return [
    'name'                      => 'Doctrine Sandbox Migrations',

    'table_storage' => [
        'table_name'                => 'doctrine_migration_versions_test',
        'version_column_name'               => 'doctrine_migration_column_test',
        'version_column_length'             => 2000,
        'executed_at_column_name'   => 'doctrine_migration_executed_at_column_test',
        'execution_time_column_name'   => 'doctrine_migration_execution_time_column_test',
    ],

    'migrations_paths'      => ['DoctrineMigrationsTest' => '.'],

    'all_or_nothing'            => true,
    'check_database_platform'   => false,
];
