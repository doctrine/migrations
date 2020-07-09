# Upgrade to 3.0

- The "version" is the FQCN of the migration class (existing entries in the migrations table will be automatically updated).
- `MigrationsEventArgs` and `MigrationsVersionEventArgs` expose different API, 
please refer to the [Code BC breaks](#code-bc-breaks) section.

## Console
- Console output changed. The commands use a different output style. If you were relying on specific output, 
  please update your scripts. 
  Console output is not covered by the BC promise, so please try not to rely on specific a output.
  Different levels of verbosity are available now (`-v`, `-vv` and `-vvv` ).
- The `--show-versions` option from `migrations:status` command has been removed, 
  use `migrations:list` instead.
- The `--write-sql` option for `migrations:migrate` and `migrations:execute` does not imply dry-run anymore,  
use the `--dry-run` parameter instead.  

## Migrations table

- The migrations table now has a new column named `execution_time`.
- Running the `migrations:migrate` or `migrations:execute` command will automatically upgrade the migration
table structure; a dedicated `migrations:sync-metadata-storage` command is available to sync manually the migrations table. 

## Migration template

- The `<version>` placeholder has been replaced by the `<className>` placeholder.

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

    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 191,
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

Note: the `name` property has been removed.

Note: the option in `table_storage` needs to be updated only if you have changed the metadata table settings
by using v2 options such as `table_name`, `column_name`, `column_length` or `executed_at_column_name`. If you did not change
those settings, it is recommended to not provide the options and let doctrine figure out the best settings.

## Code BC breaks

Most of the code is protected by the `@internal` declaration and in a very rare cases you might have dealt with the 
internals of this library. 

The most important BC breaks are in the `Doctrine\Migrations\Configuration\Configuration` class and in the helper 
system that now has been replaced by the `Doctrine\Migrations\DependencyFactory` functionalities.

Here is a list of the most important changes:

- Namespace `Doctrine\Migrations\Configuration\Configuration` 
     - CHANGED: Class `Doctrine\Migrations\Configuration\Configuration` became final
     - REMOVED: Constant `Doctrine\Migrations\Configuration\Configuration::VERSION_FORMAT` was removed, there is not more limitation on the version format
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#__construct()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setName()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getName()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getConnection()` was removed, 
     use `Doctrine\Migrations\DependencyFactory#getConnection()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsTableName()` was removed, 
     use `Doctrine\Migrations\Configuration\Configuration#setMetadataStorageConfiguration` with an instance of `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsTableName()` was removed, 
     use `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration#getMetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsColumnName()` was removed, 
     use `Doctrine\Migrations\Configuration\Configuration#setMetadataStorageConfiguration` with an instance of `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsColumnName()` was removed,
     use `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration#getMetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getQuotedMigrationsColumnName()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsColumnLength()` was removed, 
     use `Doctrine\Migrations\Configuration\Configuration#setMetadataStorageConfiguration` with an instance of `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsColumnLength()` was removed,
     use `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration#getMetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsExecutedAtColumnName()` was removed, 
     use `Doctrine\Migrations\Configuration\Configuration#setMetadataStorageConfiguration` with an instance of `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsExecutedAtColumnName()` was removed,
     use `Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration#getMetadataStorageConfiguration`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getQuotedMigrationsExecutedAtColumnName()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsDirectory()` was removed, 
     use `Doctrine\Migrations\Configuration\Configuration#addMigrationsDirectory()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsDirectory()` was removed,
     use `Doctrine\Migrations\Configuration\Configuration#getMigrationDirectories()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsNamespace()` was removed,
     use `Doctrine\Migrations\Configuration\Configuration#addMigrationsDirectory()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsNamespace()` was removed,
     use `Doctrine\Migrations\Configuration\Configuration#getMigrationDirectories()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setMigrationsFinder()` was removed, 
     use the dependency factory instead  
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsFinder()` was removed,
     use the dependency factory instead
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#hasVersionMigrated()` was removed,
     use the dependency factory instead
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getVersionData()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#resolveVersionAlias()` was removed,
     use `Doctrine\Migrations\Version\AliasResolver#resolveVersionAlias()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#isMigrationTableCreated()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#createMigrationTable()` was removed,
     use `Doctrine\Migrations\Metadata\Storage\MetadataStorage#ensureInitialized()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getDateTime()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#generateVersionNumber()` was removed,
     use `Doctrine\Migrations\Generator\ClassNameGenerator#generateClassName()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#connect()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#dispatchMigrationEvent()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#dispatchVersionEvent()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#dispatchEvent()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getNumberOfExecutedMigrations()` was removed,
     use `Doctrine\Migrations\DependencyFactory#getMetadataStorage()->getExecutedMigrations()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getNumberOfAvailableMigrations()` was removed, 
     use `Doctrine\Migrations\DependencyFactory#getMigrationRepository()->getMigrations()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getLatestVersion()` was removed, 
     use `Doctrine\Migrations\Version\AliasResolver#resolveVersionAlias()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigratedVersions()` was removed, 
     use `Doctrine\Migrations\DependencyFactory#getMetadataStorage()->getExecutedMigrations()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getAvailableVersions()` was removed
     use `Doctrine\Migrations\DependencyFactory#getMigrationRepository()->getMigrations()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getCurrentVersion()` was removed,
     use `Doctrine\Migrations\Version\AliasResolver#resolveVersionAlias()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#registerMigrationsFromDirectory()` was removed,
     use `Doctrine\Migrations\Configuration\Configuration#addMigrationsDirectory()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#registerMigration()` was removed,
     use `Doctrine\Migrations\Configuration\Configuration#addMigrationClass()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#registerMigrations()` was removed
     use `Doctrine\Migrations\Configuration\Configuration#addMigrationClass()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrations()` was removed,
     use `Doctrine\Migrations\DependencyFactory#getMigrationRepository()->getMigrations()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getVersion()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getMigrationsToExecute()` was removed,
     use `Doctrine\Migrations\Version\MigrationPlanCalculator#getPlanUntilVersion()` to create a migration plan
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getPrevVersion()` was removed,
     use `Doctrine\Migrations\Version\AliasResolver#resolveVersionAlias()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getNextVersion()` was removed,
     use `Doctrine\Migrations\Version\AliasResolver#resolveVersionAlias()`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getRelativeVersion()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getDeltaVersion()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#setOutputWriter()` was removed, 
        set the `Psr\Log\LoggerInterface` service in `Doctrine\Migrations\DependencyFactory` 
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getOutputWriter()` was removed,
     get the `Psr\Log\LoggerInterface` service from `Doctrine\Migrations\DependencyFactory`
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getQueryWriter()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#getDependencyFactory()` was removed
     - REMOVED: Method `Doctrine\Migrations\Configuration\Configuration#validate()` was removed
     - Namespace `Doctrine\Migrations\Configuration\Connection\Loader\Exception`
         - REMOVED: Class `Doctrine\Migrations\Configuration\Connection\Loader\Exception\LoaderException` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Connection\Loader\Exception\InvalidConfiguration` has been deleted
     - Namespace `Doctrine\Migrations\Configuration\Exception`
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\ParameterIncompatibleWithFinder` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\MigrationsNamespaceRequired` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\XmlNotValid` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\YamlNotAvailable` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\FileAlreadyLoaded` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\JsonNotValid` has been deleted
         - REMOVED: Class `Doctrine\Migrations\Configuration\Exception\YamlNotValid` has been deleted
         - CHANGED: The number of required arguments for `Doctrine\Migrations\Configuration\Exception\FileNotFound::new()` increased from 0 to 1
 - Namespace `Doctrine\Migrations\Event\MigrationsEventArgs`
     - CHANGED: Class `Doctrine\Migrations\Event\MigrationsEventArgs` became final
     - REMOVED: Method `Doctrine\Migrations\Event\MigrationsEventArgs#getConfiguration()` was removed
     - REMOVED: Method `Doctrine\Migrations\Event\MigrationsEventArgs#getDirection()` was removed,
     use `Doctrine\Migrations\Event\MigrationsEventArgs#getPlan()`
     - REMOVED: Method `Doctrine\Migrations\Event\MigrationsEventArgs#isDryRun()` was removed, 
     use `Doctrine\Migrations\Event\MigrationsEventArgs#getMigratorConfiguration()`
     - CHANGED: `Doctrine\Migrations\Event\MigrationsEventArgs#__construct()` arguments have been updated
    - Namespace `Doctrine\Migrations\Event\MigrationsVersionEventArgs`    
         - CHANGED: Class `Doctrine\Migrations\Event\MigrationsVersionEventArgs` became final
         - REMOVED: Method `Doctrine\Migrations\Event\MigrationsVersionEventArgs#getVersion()` was removed
         use `Doctrine\Migrations\Event\MigrationsEventArgs#getPlan()`
 - Namespace `Doctrine\Migrations\Finder`
     - REMOVED: These ancestors of `Doctrine\Migrations\Finder\RecursiveRegexFinder` have been removed: ["Doctrine\\Migrations\\Finder\\MigrationDeepFinder"]
     - REMOVED: Class `Doctrine\Migrations\Finder\MigrationDeepFinder` has been deleted
 - Namespace `Doctrine\Migrations\Tools\Console\Command` 
     - CHANGED: All non abstract classes in `Doctrine\Migrations\Tools\Console\Command\*` became final
     - REMOVED: Class `Doctrine\Migrations\Tools\Console\Command\AbstractCommand` has been renamed into `Doctrine\Migrations\Tools\Console\Command\DoctrineCommand` and has been marked as internal 
     - CHANGED: Method `Doctrine\Migrations\Tools\Console\Command\*Command#__construct()` changed signature into  `(?Doctrine\Migrations\DependencyFactory $di, ?string $name)`
     - CHANGED: Method `initialize()` of Class `Doctrine\Migrations\Tools\Console\Command\AbstractCommand` visibility reduced from `public` to `protected`
     - CHANGED: Method `execute()` of Class `Doctrine\Migrations\Tools\Console\Command\*Command` visibility reduced from `public` to `protected`
     - REMOVED: Method `Doctrine\Migrations\Tools\Console\Command\DiffCommand#createMigrationDiffGenerator()` was removed
     - Namespace `Doctrine\Migrations\Tools\Console\Exception`
         - CHANGED: The number of required arguments for `Doctrine\Migrations\Tools\Console\Exception\SchemaDumpRequiresNoMigrations::new()` increased from 0 to 1
         - REMOVED: Class `Doctrine\Migrations\Tools\Console\Exception\ConnectionNotSpecified` has been deleted
     - Namespace `Migrations\Tools\Console\Helper`
         - REMOVED: All classes and namespaces are marked as internal or have been removed, 
         use `Doctrine\Migrations\DependencyFactory` instead
 - Namespace `Doctrine\Migrations\AbstractMigration`       
     - CHANGED: The method `Doctrine\Migrations\AbstractMigration#__construct()` changed signature into `(Doctrine\DBAL\Connection $conn, PSR\Log\LoggerInterface $logger)`
     - CHANGED: The method `Doctrine\Migrations\AbstractMigration#down()` is not abstract anymore, the default implementation will abort the migration process
     - REMOVED: Property `Doctrine\Migrations\AbstractMigration#$version` was removed 
 - Namespace `Doctrine\Migrations\Provider`
     - REMOVED: Class `Doctrine\Migrations\Provider\SchemaProviderInterface` has been deleted
     - REMOVED: These ancestors of `Doctrine\Migrations\Provider\StubSchemaProvider` have been removed: ["Doctrine\\Migrations\\Provider\\SchemaProviderInterface"]
 - Namespace `Doctrine\Migrations\Exception`        
     - REMOVED: Class `Doctrine\Migrations\Exception\MigrationNotConvertibleToSql` has been deleted
     - REMOVED: Class `Doctrine\Migrations\Exception\MigrationsDirectoryRequired` has been deleted
 - REMOVED: Class `Doctrine\Migrations\Version\Factory` became the interface `Doctrine\Migrations\Version\MigrationFactory`
 - REMOVED: Class `Doctrine\Migrations\OutputWriter` has been deleted, 
 use `Psr\Log\Loggerinterface` 




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
