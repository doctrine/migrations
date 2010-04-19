# Doctrine 2 Migrations

The Doctrine migrations offer additional functionality on top of the database
abstraction layer (DBAL) for versioning your database schema and easily deploying
changes to it. It is easy to use and a very powerful tool! Continue reading to
learn a bit about migrations.

## The Concept

Migrations allow you to version your database schema and easily deploy it
across multiple database servers. Each version is represented by a single PHP5
class that allows you to manipulate your schema with an OO interface or by 
manually writing SQL statements to be executed.

This means you can easily commit new migration classes to your version control
system allowing other developers to pull them down and update their development
database or even update production database servers.

## Setup

In order to use the Migrations extension you need to do a little integration to
make sure the extensions classes can be loaded.

### Class Loaders

First setup the class loader to load the classes for the Doctrine\DBAL\Migrations
namespace in your project:

    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL\Migrations', '/path/to/migrations/lib');
    $classLoader->register();

Now the above autoloader is able to load a class like the following:

    /path/to/migrations/lib/Doctrine/DBAL/Migrations/Migrations/Migration.php

Along with this we will need to be able to autoload our actual migration classes:

    $classLoader = new \Doctrine\Common\ClassLoader('DoctrineMigrations', '/path/to/migrations');
    $classLoader->register();

This autoloader is able to load classes like the following:

    /path/to/migrations/DoctrineMigrations/VersionYYYYMMDDHHMMSS.php

### Console Commands

Now that we have setup the autoloaders we are ready to add the migration console
commands:

    $cli->addCommands(array(
        // ...

        // Migrations Commands
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
    ));

You will see that you have a few new commands when you execute the following command:

    $ ./doctrine list migrations
    Doctrine Command Line Interface version 2.0-DEV

    Usage:
      [options] command [arguments]

    Options:
      --help           -h Display this help message.
      --quiet          -q Do not output any message.
      --verbose        -v Increase verbosity of messages.
      --version        -V Display this program version.
      --color          -c Force ANSI color output.
      --no-interaction -n Do not ask any interactive question.

    Available commands for the "migrations" namespace:
      :diff      Generate a migration by comparing your current database to your mapping information.
      :execute   Execute a single migration version up or down manually.
      :generate  Generate a blank migration class.
      :migrate   Execute a migration to a specified version or the latest available version.
      :status    View the status of a set of migrations.
      :version   Manually add and delete migration versions from the version table.

## Configuration

The last thing you need to do is to configure your migrations. You can do so
by using the _--configuration_ option to manually specify the path
to a configuration file. If you don't specify any configuration file the tasks will
look for a file named _migrations.xml_ or _migrations.yml_ at the root of
your command line. For the upcoming examples you can use a _migrations.xml_
file like the following:

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-migrations xmlns="http://doctrine-project.org/schemas/migrations/configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://doctrine-project.org/schemas/migrations/configuration
                        http://doctrine-project.org/schemas/migrations/configuration.xsd">

        <name>Doctrine Sandbox Migrations</name>

        <migrations-namespace>DoctrineMigrations</migrations-namespace>

        <table name="doctrine_migration_versions" />

        <migrations-directory>/path/to/DoctrineMigrations</migrations-directory>

    </doctrine-migrations>

Of course you could do the same thing with a _configuration.yml_ file:

    name: Doctrine Sandbox Migrations
    migrations_namespace: DoctrineMigrations
    table_name: doctrine_migration_versions
    migrations_directory: /path/to/migrations/DoctrineMigrations

And if you want to specify each migration manually in YAML you can:

    table_name: doctrine_migration_versions
    migrations_directory: /path/to/migrations/DoctrineMigrations
    migrations:
      migration1:
        version: 1
        class: DoctrineMigrations\NewMigration

## Migration Classes

As now everything is setup and configured you are ready to start writing
migration classes. You can easily generate your first migration class with the
following command:

    $ ./doctrine migrations:generate
    Generated new migration class to "/path/to/migrations/DoctrineMigrations/Version20100416130401.php"

Have a look and you will see a new class at the above location that looks like
the following:

    <?php

    namespace DoctrineMigrations;

    use Doctrine\DBAL\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100416130401 extends AbstractMigration
    {
        public function up(Schema $schema)
        {

        }

        public function down(Schema $schema)
        {

        }
    }

Now that we have a new migration class present, lets run the status task to see
if it is there:

    $ ./doctrine migrations:status

     == Configuration

        >> Name:                                               Doctrine Sandbox Migrations
        >> Configuration Source:                               /Users/jwage/Sites/doctrine2git/tools/sandbox/migrations.xml
        >> Version Table Name:                                 doctrine_migration_versions
        >> Migrations Namespace:                               DoctrineMigrations
        >> Migrations Directory:                               /Users/jwage/Sites/doctrine2git/tools/sandbox/DoctrineMigrations
        >> Current Version:                                    2010-04-16 13:04:22 (20100416130422)
        >> Latest Version:                                     2010-04-16 13:04:22 (20100416130422)
        >> Executed Migrations:                                0
        >> Available Migrations:                               1
        >> New Migrations:                                     1

     == Migration Versions

        >> 2010-04-16 13:04:01 (20100416130401)                not migrated

As you can see we have a new version present and it is ready to be executed. The
problem is it does not have anything in it so nothing would be executed! Let's
add some code to it and add a new table:

    <?php

    namespace DoctrineMigrations;

    use Doctrine\DBAL\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100416130401 extends AbstractMigration
    {
        public function up(Schema $schema)
        {
            $table = $schema->createTable('users');
            $table->addColumn('username', 'string');
            $table->addColumn('password', 'string');
        }

        public function down(Schema $schema)
        {
            $schema->dropTable('users');
        }
    }

Now we are ready to give it a test! First lets just do a dry-run to make sure
it produces the SQL we expect:

    $ ./doctrine migrations:migrate --dry-run
    Are you sure you wish to continue?
    y
    Executing dry run of migration up to 20100416130452 from 0

      ++ migrating 20100416130452

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

Everything looks good so we can remove the --dry-run option and actually execute
the migration:

    $ ./doctrine migrations:migrate
    Are you sure you wish to continue?
    y
    Migrating up to 20100416130452 from 0

      ++ migrating 20100416130452

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

      ++ migrated

By checking the status again you will see everything is updated:

    $ ./doctrine migrations:status

     == Configuration

        >> Name:                                               Doctrine Sandbox Migrations
        >> Configuration Source:                               /Users/jwage/Sites/doctrine2git/tools/sandbox/migrations.xml
        >> Version Table Name:                                 doctrine_migration_versions
        >> Migrations Namespace:                               DoctrineMigrations
        >> Migrations Directory:                               /Users/jwage/Sites/doctrine2git/tools/sandbox/DoctrineMigrations
        >> Current Version:                                    2010-04-16 13:04:52 (20100416130452)
        >> Latest Version:                                     2010-04-16 13:04:52 (20100416130452)
        >> Executed Migrations:                                1
        >> Available Migrations:                               1
        >> New Migrations:                                     0

     == Migration Versions

        >> 2010-04-16 13:04:01 (20100416130452)                migrated

## Manual SQL Migrations

Sometimes you need to do some complex migration operations which force you
to use plain SQL statements. Using the __addSql()_ this is possible within any
migration class.

First you need to generate a new migration class:

    $ ./doctrine migrations:generate
    Generated new migration class to "/path/to/migrations/DoctrineMigrations/Version20100416130422.php"

This newly generated migration class is the place where you can add your own
custom SQL queries:

    <?php

    namespace DoctrineMigrations;

    use Doctrine\DBAL\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100416130422 extends AbstractMigration
    {
        public function up(Schema $schema)
        {
            $this->_addSql('CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB');
        }

        public function down(Schema $schema)
        {
            $this->_addSql('DROP TABLE addresses');
        }
    }

When running the migration it simply executes the SQL in the order you add it:

    $ ./doctrine migrations:migrate
    Are you sure you wish to continue?
    y
    Migrating up to 20100416130422 from 20100416130401

      ++ migrating 20100416130422

         -> CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB

      ++ migrated

## Reverting Migrations

You maybe noticed in the last example that we defined a _down()_ method which
drops the users table that we created. This method allows us to easily revert
changes the schema has been migrated to. The _migrate_ command takes a _version_
argument which you can use to roll back your schema to a specific version of
your migrations:

    $ ./doctrine migrations:migrate 0
    Are you sure you wish to continue?
    y
    Migrating down to 0 from 20100416130422

      -- reverting 20100416130422

         -> DROP TABLE addresses

      -- reverted

      -- reverting 20100416130401

         -> DROP TABLE users

      -- reverted

Now our database is back to where we originally started. Give it a check with
the status command:

    $ ./doctrine migrations:status

     == Configuration

        >> Name:                                               Doctrine Sandbox Migrations
        >> Configuration Source:                               /Users/jwage/Sites/doctrine2git/tools/sandbox/migrations.xml
        >> Version Table Name:                                 doctrine_migration_versions
        >> Migrations Namespace:                               DoctrineMigrations
        >> Migrations Directory:                               /Users/jwage/Sites/doctrine2git/tools/sandbox/DoctrineMigrations
        >> Current Version:                                    0
        >> Latest Version:                                     2010-04-16 13:04:22 (20100416130422)
        >> Executed Migrations:                                0
        >> Available Migrations:                               2
        >> New Migrations:                                     2

     == Migration Versions

        >> 2010-04-16 13:04:01 (20100416130401)                not migrated
        >> 2010-04-16 13:04:22 (20100416130422)                not migrated

## Writing Migration SQL Files

You can optionally choose to not execute a migration directly on a database and
instead output all the SQL statements to a file. This is possible by using the
_--write-sql_ option of the _migrate_ command:

    $ ./doctrine migrations:migrate --write-sql
    Executing dry run of migration up to 20100416130422 from 0

      ++ migrating 20100416130401

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

      ++ migrating 20100416130422

         -> CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB

    Writing migration file to "/path/to/sandbox/doctrine_migration_20100416130405.sql"

Now if you have a look at the _doctrine_migration_20100416130405.sql_ file you will see the would be
executed SQL outputted in a nice format:

    # Doctrine Migration File Generated on 2010-04-16 13:04:05
    # Migrating from 0 to 20100416130422

    # Version 20100416130401
    CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB;

    # Version 20100416130422
    CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;

## Managing the Version Table

Sometimes you may need to manually change something in the database table which
manages the versions for some migrations. For this you can use the version task.
You can easily add a version like this:

    $ ./doctrine migrations:version YYYYMMDDHHMMSS --add

Or you can delete that version:

    $ ./doctrine migrations:version YYYYMMDDHHMMSS --delete

The command does not execute any migrations code, it simply adds the specified
version to the database.

## Generating Migrations from ORM Mapping Information

If you are using the Doctrine 2 ORM you can easily generate a migration class
by modifying your mapping information and running the diff task to compare it
to your current database schema.

If you are using the sandbox you can modify the provided yaml/Entities.User.dcm.yml
and add a new column:

    Entities\User:
      # ...
      fields:
        # ...
        test:
          type: string
          length: 255
      # ...

Be sure that you add the property to the Entities/User.php file:

    <?php

    namespace Entities;

    /** @Entity @Table(name="users") */
    class User
    {
        /**
         * @var string $test
         */
        private $test;

        // ...
    }

Now if you run the diff task you will get a nicely generated migration with the
changes required to update your database!

    $ ./doctrine migrations:diff
    Generated new migration class to "/path/to/migrations/DoctrineMigrations/Version20100416130459.php" from schema differences.

The migration class that is generated contains the SQL statements required to 
update your database:

    <?php

     namespace DoctrineMigrations;

     use Doctrine\DBAL\Migrations\AbstractMigration,
         Doctrine\DBAL\Schema\Schema;

     class Version20100416130459 extends AbstractMigration
     {
         public function up(Schema $schema)
         {
             $this->_addSql('ALTER TABLE users ADD test VARCHAR(255) NOT NULL');
         }

         public function down(Schema $schema)
         {
             $this->_addSql('ALTER TABLE users DROP test');
         }
     }

The SQL generated here is the exact same SQL that would be executed if you were
using the orm:schema-tool task and the --update option. This just allows you to
capture that SQL and maybe tweak it or add to it and trigger the deployment
later across multiple database servers.