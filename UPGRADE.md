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
internals of this library. If you did, this are the main changes in the 3.0 release.


# Added
 - [BC] Method getConfiguration() was added to interface Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelperInterface

# Changed
 - [BC] The parameter `$config` of `Doctrine\Migrations\Event\MigrationsEventArgs#__construct()` changed from Doctrine\Migrations\Configuration\Configuration to a non-contravariant Doctrine\DBAL\Connection
 - [BC] The parameter `$direction` of `Doctrine\Migrations\Event\MigrationsEventArgs#__construct()` changed from string to a non-contravariant Doctrine\Migrations\Metadata\MigrationPlanList
 - [BC] The parameter `$dryRun` of `Doctrine\Migrations\Event\MigrationsEventArgs#__construct()` changed from bool to a non-contravariant Doctrine\Migrations\MigratorConfiguration
 - [BC] The parameter `$config` of `Doctrine\Migrations\Event\MigrationsEventArgs#__construct()` changed from Doctrine\Migrations\Configuration\Configuration to Doctrine\DBAL\Connection
 - [BC] The parameter `$direction` of `Doctrine\Migrations\Event\MigrationsEventArgs#__construct()` changed from string to Doctrine\Migrations\Metadata\MigrationPlanList
 - [BC] The parameter `$dryRun` of `Doctrine\Migrations\Event\MigrationsEventArgs#__construct()` changed from bool to Doctrine\Migrations\MigratorConfiguration
 - [BC] The parameter `$version` of `Doctrine\Migrations\Event\MigrationsVersionEventArgs#__construct()` changed from Doctrine\Migrations\Version\Version to a non-contravariant Doctrine\DBAL\Connection
 - [BC] The parameter `$config` of `Doctrine\Migrations\Event\MigrationsVersionEventArgs#__construct()` changed from Doctrine\Migrations\Configuration\Configuration to a non-contravariant Doctrine\Migrations\Metadata\MigrationPlan
 - [BC] The parameter `$direction` of `Doctrine\Migrations\Event\MigrationsVersionEventArgs#__construct()` changed from string to a non-contravariant Doctrine\Migrations\MigratorConfiguration
 - [BC] The parameter `$version` of `Doctrine\Migrations\Event\MigrationsVersionEventArgs#__construct()` changed from Doctrine\Migrations\Version\Version to Doctrine\DBAL\Connection
 - [BC] The parameter `$config` of `Doctrine\Migrations\Event\MigrationsVersionEventArgs#__construct()` changed from Doctrine\Migrations\Configuration\Configuration to Doctrine\Migrations\Metadata\MigrationPlan
 - [BC] The parameter `$direction` of `Doctrine\Migrations\Event\MigrationsVersionEventArgs#__construct()` changed from string to Doctrine\Migrations\MigratorConfiguration
 - [BC] The parameter `$schemaProvider` of `Doctrine\Migrations\Tools\Console\Command\DiffCommand#__construct()` changed from ?Doctrine\Migrations\Provider\SchemaProviderInterface to a non-contravariant ?string
 - [BC] The parameter `$schemaProvider` of `Doctrine\Migrations\Tools\Console\Command\DiffCommand#__construct()` changed from ?Doctrine\Migrations\Provider\SchemaProviderInterface to ?string
 - [BC] Type documentation for property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` changed from \Doctrine\Migrations\DependencyFactory to \Doctrine\Migrations\DependencyFactory|null
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` visibility reduced from protected to private
 - [BC] The parameter `$connection` of `Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper#__construct()` changed from Doctrine\DBAL\Connection to a non-contravariant ?Doctrine\Migrations\Configuration\ConfigurationLoader
 - [BC] The parameter `$connection` of `Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper#__construct()` changed from Doctrine\DBAL\Connection to ?Doctrine\Migrations\Configuration\ConfigurationLoader
 - [BC] The number of required arguments for `Doctrine\Migrations\AbstractMigration#__construct()` increased from 1 to 2
 - [BC] The parameter `$version` of `Doctrine\Migrations\AbstractMigration#__construct()` changed from Doctrine\Migrations\Version\Version to a non-contravariant Doctrine\DBAL\Connection
 - [BC] The parameter `$version` of `Doctrine\Migrations\AbstractMigration#__construct()` changed from Doctrine\Migrations\Version\Version to Doctrine\DBAL\Connection
 - [BC] The parameter `$configuration` of `Doctrine\Migrations\Version\Factory#__construct()` changed from Doctrine\Migrations\Configuration\Configuration to Doctrine\DBAL\Connection
 - [BC] The parameter `$versionExecutor` of `Doctrine\Migrations\Version\Factory#__construct()` changed from Doctrine\Migrations\Version\ExecutorInterface to Psr\Log\LoggerInterface
 - [BC] The return type of `Doctrine\Migrations\Version\Factory#createVersion()` changed from Doctrine\Migrations\Version\Version to the non-covariant Doctrine\Migrations\AbstractMigration
 - [BC] The return type of `Doctrine\Migrations\Version\Factory#createVersion()` changed from Doctrine\Migrations\Version\Version to Doctrine\Migrations\AbstractMigration

# Removed
 - [BC] Method `Doctrine\Migrations\Event\MigrationsEventArgs#getConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Event\MigrationsEventArgs#getDirection()` was removed
 - [BC] Method `Doctrine\Migrations\Event\MigrationsEventArgs#isDryRun()` was removed
 - [BC] Method `Doctrine\Migrations\Event\MigrationsVersionEventArgs#getVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Event\MigrationsEventArgs#getConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Event\MigrationsEventArgs#getDirection()` was removed
 - [BC] Method `Doctrine\Migrations\Event\MigrationsEventArgs#isDryRun()` was removed
 - [BC] These ancestors of `Doctrine\Migrations\Event\MigrationsVersionEventArgs` have been removed: ["Doctrine\\Migrations\\Event\\MigrationsEventArgs"]
 - [BC] These ancestors of `Doctrine\Migrations\Finder\RecursiveRegexFinder` have been removed: ["Doctrine\\Migrations\\Finder\\MigrationDeepFinder"]
 - [BC] Class `Doctrine\Migrations\Finder\MigrationDeepFinder` has been deleted
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\DiffCommand#$schemaProvider` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\DiffCommand#createMigrationDiffGenerator()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\MigrateCommand#createMigrator()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$configuration` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$connection` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$dependencyFactory` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationRepository` was removed
 - [BC] Property `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#$migrationConfiguration` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setDependencyFactory()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#setMigrationRepository()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Command\AbstractCommand#getMigrationConfiguration()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper#getMigrationConfig()` was removed
 - [BC] Method `Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelperInterface#getMigrationConfig()` was removed
 - [BC] Property `Doctrine\Migrations\AbstractMigration#$version` was removed
 - [BC] Class `Doctrine\Migrations\Configuration\Exception\ParameterIncompatibleWithFinder` has been deleted
 - [BC] Class `Doctrine\Migrations\Configuration\Exception\MigrationsNamespaceRequired` has been deleted
 - [BC] Class `Doctrine\Migrations\Configuration\Exception\FileAlreadyLoaded` has been deleted
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#__construct()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getConnection()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsTableName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsTableName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsColumnName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsColumnName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getQuotedMigrationsColumnName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsColumnLength()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsColumnLength()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsExecutedAtColumnName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsExecutedAtColumnName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getQuotedMigrationsExecutedAtColumnName()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsDirectory()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsDirectory()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsNamespace()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsNamespace()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsFinder()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsFinder()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#hasVersionMigrated()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getVersionData()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#resolveVersionAlias()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#isMigrationTableCreated()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#createMigrationTable()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getDateTime()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#connect()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#dispatchMigrationEvent()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#dispatchVersionEvent()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#dispatchEvent()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getNumberOfExecutedMigrations()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getNumberOfAvailableMigrations()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getLatestVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigratedVersions()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getAvailableVersions()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getCurrentVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#registerMigrationsFromDirectory()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#registerMigration()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#registerMigrations()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrations()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#hasVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsToExecute()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getPrevVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getNextVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getRelativeVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getDeltaVersion()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#setOutputWriter()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getOutputWriter()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getQueryWriter()` was removed
 - [BC] Method `Doctrine\Migrations\Configuration\Configuration#getDependencyFactory()` was removed
 - [BC] Class `Doctrine\Migrations\Exception\MigrationNotConvertibleToSql` has been deleted
 - [BC] Class `Doctrine\Migrations\OutputWriter` has been deleted

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
