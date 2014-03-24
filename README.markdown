# Doctrine Migrations

Migrations helps developers migrate database schema and data in a simple and reproducible way.
It is currently in alpha version after a full rewrite of its precedessor, version 1.0 that
never left alpha status.

The goal of the rewrite is to reach a stable version as soon as possible,
hopefully leaving the long period of rather unstable migrations in Doctrine
behind us.

The current verion aims to solve various problems:

- Remove reliance on Schema API of DBAL at runtime, because it proved to be a source
  of bugs and when executed with reproduceability in mind.
- Remove ability to migrate down. Downmigrations are not possible for 100% of up cases
  and you can therefore not rely on this feature to work. Providing this feature
  only adds a false sense of safety that can bite you hard.
- Remove ability to generate migrations by diff. This is not a feature Migrations
  should provide, instead it only works when you have two schemas present like
  in the ORM and should be provided there.
- Introduce SQL file based migrations as default for the 80% use-case, leaving
  PHP based migrations for only complex scenarios.
- Improve ability to check executed SQL beforehand (dry-run).
- Improve information about migrations
- Add explicit handling for failed migrations
- Simplify code-base and reduce code complexity, decouple core from DBAL and Symfony Console.
  This should improve integration of Migrations into other libraries and frameworks.

## Todos

- Add Symfony Console and PHAR Generation
- Tests for migration failures
- Dry-Run mode
- Multi-Platform migrations (with subdirectory SqlFileLoader)
- Documentation

## Introduction

Doctrine Migrations updates a database schema from one version to another using
a concept called migration.  Each migration has a version and a description.
Versions **MUST** be unique.

From a configured directory Doctrine loads SQL- and PHP-based migrations, where
SQL is used for simple tasks and PHP can be used for more complex operations.

Doctrine sorts all migrations by version and executes them in order.

Migration files have to be in the following format or an error will be thrown:

    V[version]_[description].[ext]

Valid filenames include:

- ``V1_Test.sql``
- ``V1.1_TestPatch.php``
- ``V2_Add_New_Column.sql``

SQL migrations contain just SQL statements to be executed.

DBAL PHP Migrations need to implement an interface ``Doctrine\Migrations\DBAL\DBALMigration``:

```php
<?php

use Doctrine\Migrations\DBAL\DBALMigration;
use Doctrine\DBAL\Connection;

class V2_Batch implements DBALMigration
{
    public function migrate(Connection $connection)
    {
        $connection->insert('test', array('id' => 1, 'val' => 'Hello World!'));
    }
}
```

## Usage

You can use Doctrine Migrations in two ways:

- As a standalone PHAR
- Embedded into your application or framework

### Standalone Phar

In case of the standalone PHAR, you need to provide a configuration file named
``migrations.ini`` in the direcetory you are executing the migrations command
in. The file format is INI, here is an example:

    [migrations]
    script_directory = "migrations/"
    allow_init_on_migrate = true
    validate_on_migrate = true
    out_of_order_migrations_allowed = true

    [db]
    driver = "pdo_mysql"
    host = "localhost"
    dbname = "mydb"
    user = "root"
    password = ""

You can also pass the path to the file as an option to the migrations command.

### Embedded

To use migrations embedded you need to obtain an instance of ``Doctrine\Migrations\Migrations``,
which is a facade to all migration operations.

The dependencies for this object are:

- An instance of ``Doctrine\Migrations\Configuration``
- An implementation of ``Doctrine\Migrations\MetadataStorage``, when using DBAL and a metadata table
  you can just use ``Doctrine\Migrations\DBAL\TableMetadataStorage``.
- An instance of ``Doctrine\Migrations\Executor\ExecutorRegistry`` with at least one or many
  executors attached that can perform migrations.
- An implementation of ``Doctrine\Migrations\Loader\Loader`` that knows how to load migrations.

You can see an example of this in the ``Doctrine\Migrations\DBAL\Factory`` class that is used
by the standalone component:

```php
use Doctrine\Migrations\Migrations;
use Doctrine\Migrations\Configuration;
use Doctrine\Migrations\Loader\ChainLoader;
use Doctrine\Migrations\Executor\ExecutorRegistry;
use Doctrine\Migrations\DBAL\Loader;
use Doctrine\Migrations\DBAL\Executor;
use Doctrine\DBAL\DriverManager;

$configuration = new Configuration();
$connection = DriverManager::getConnection($data['db']);

$chainLoader = new ChainLoader();
$chainLoader->add(new Loader\SqlFileLoader());
$chainLoader->add(new Loader\PhpFileLoader());

$executorRegistry = new ExecutorRegistry();
$executorRegistry->add(new Executor\SqlFileExecutor($connection));
$executorRegistry->add(new Executor\PhpFileExecutor($connection));

$migrations = new Migrations(
    $configuration,
    new TableMetadataStorage($connection, $configuration->getScriptDirectory()),
    $chainLoader,
    $executorRegistry
);
```
