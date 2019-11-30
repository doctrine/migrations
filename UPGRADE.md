# Upgrade to 3.0

- The "version" is the FQCN of the migration class (existing entries in the migrations table will be automatically updated).
- `MigrationsEventArgs` and `MigrationsVersionEventArgs` expose different API, 
please refer to the [Code BC breaks](#code-bc-breaks) section.

## Console
- Console output changed. The commands use a different output style. If you were relying on specific output, 
  please update your scripts. 
  Console output is not covered by the BC promise, so please try not to rely on specific a output.
  Different levels of verbosity are available now (`-v`, `-vv` and `-vvv` ).

## Migrations table

- The migrations table now has a new column named `execution_time`.


## Configuration files

*migrations.php Before*
```php
<?php

return [
    'name' => 'My Project Migrations',
    'migrations_namespace' => 'MyProject\Migrations',
    'table_name' => 'doctrine_migration_versions',
    'column_name' => 'version',
    'column_length' => 14,
    'executed_at_column_name' => 'executed_at',
    'migrations_directory' => '/data/doctrine/migrations-docs-example/lib/MyProject/Migrations',
    'all_or_nothing' => true,
    'check_database_platform' => true,
];
```
*migrations.php After*

```php
<?php

return [
    'name' => 'My Project Migrations',

    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 1024,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],

    'migrations_paths' => [
        'MyProject\Migrations' => '/data/doctrine/migrations/lib/MyProject/Migrations',
        'MyProject\Component\Migrations' => './Component/MyProject/Migrations',
    ],

    'all_or_nothing' => true,
    'check_database_platform' => true,
];
```

Files in XML, YAML or JSON also changed in a similar way. Please refer to the official documentation for more details.

## Code BC breaks

Most of the code is protected by the `@internal` declaration and in a very rare cases you might have dealt with the 
internals of this library. 
You can use [Roave/BackwardCompatibilityCheck](https://github.com/Roave/BackwardCompatibilityCheck) and get a list of 
changed elements.

# Upgrade to 2.0

## BC Break: Moved `Doctrine\DBAL\Migrations` to `Doctrine\Migrations`

Your migration classes that previously used to extend `Doctrine\DBAL\Migrations\AbstractMigration` now need to extend
`Doctrine\Migrations\AbstractMigration` instead. The `Doctrine\DBAL\Migrations\AbstractMigration` class will be
deprecated in the `1.8.0` release to prepare for the BC break.

## BC Break: Removed `Doctrine\DBAL\Migrations\MigrationsVersion`

The `Doctrine\DBAL\Migrations\MigrationsVersion` class is no longer available: please refrain from checking the Migrations version at runtime.

## BC Break: Moved `Doctrine\Migrations\Migration` to `Doctrine\Migrations\Migrator`

To make the name more clear and to differentiate from the `AbstractMigration` class, `Migration` was renamed to `Migrator`.

## BC Break: Moved exception classes from `Doctrine\Migrations\%name%Exception` to `Doctrine\Migrations\Exception\%name%`
doctrine/migrations#636
Follows concept introduced in ORM (doctrine/orm#6743 + doctrine/orm#7210) and naming follows pattern accepted in Doctrine CS.

# Upgrade from 1.0-alpha1 to 1.0.0-alpha3

## AbstractMigration

### Before:

The method `getName()` was defined and it's implementation would change the order in which the migration would be processed.
It would cause discrepancies between the file order in a file browser and the order of execution of the migrations.

### After:

The `getName()` method as been removed | set final and new `getDescription()` method has been added.
The goal of this method is to be able to provide context for the migration.
This context is shown for the last migrated migration when the status command is called.

## --write-sql option from the migrate command

### Before:

The `--write-sql` option would only output sql contained in the migration and would not update the table containing the migrated migrations.

### After:

That option now also output the sql queries necessary to update the table containing the state of the migrations.
If you want to go back to the previous behavior just make a request on the bug tracker as for now the need for it is not very clear.

## MigrationsVersion::VERSION

### Before:

`MigrationsVersion::VERSION` used to be a property.
The returned value was fanciful.

### After:

It is now a a function so that a different value can be automatically send back if it's a modified version that's used.
The returned value is now the git tag.
The tag is in lowercase as the other doctrine projects.
