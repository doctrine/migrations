<?php

declare(strict_types=1);

return [
    'name'                      => 'Doctrine Sandbox Migrations',
    'migrations_namespace'      => 'DoctrineMigrationsTest',
    'table_name'                => 'doctrine_migration_versions_test',
    'column_name'               => 'doctrine_migration_column_test',
    'column_length'             => 200,
    'executed_at_column_name'   => 'doctrine_migration_executed_at_column_test',
    'migrations_directory'      => '.',
    'migrations'                => [],
    'all_or_nothing'            => true,
];
