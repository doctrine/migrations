# Doctrine 2 Migrations

The Doctrine migrations offer additional functionality on top of the database
abstraction layer for versioning your database schema and easily deploying
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

First setup the class loader to load the classes for the DoctrineExtensions
namespace in your project:

    $classLoader = new \Doctrine\Common\ClassLoader('DoctrineExtensions', '/path/to/extensions');
    $classLoader->register();

Now the above autoloader is able to load a class like the following:

    /path/to/extensions/DoctrineExtensions/Migrations/Migration.php

Along with this we will need to be able to autoload our actual migration classes:

    $classLoader = new \Doctrine\Common\ClassLoader('DoctrineMigrations', '/path/to/migrations');
    $classLoader->register();

This autoloader is able to load classes like the following:

    /path/to/migrations/DoctrineMigrations/VersionYYYYMMDDHHMMSS.php

### Command Line Tasks

NOTE: Currently, you also need to add the tasks manually to integrate them
into your CLI.

Now that we have setup the autoloaders we are ready to add our command line
tasks to your CliController:

    $cli = new \Doctrine\Common\Cli\CliController($configuration);

    $cli->addNamespace('Migrations')
            ->addTask('migrate', 'DoctrineExtensions\Migrations\Tools\Cli\Tasks\MigrateTask')
            ->addTask('version', 'DoctrineExtensions\Migrations\Tools\Cli\Tasks\VersionTask')
            ->addTask('generate', 'DoctrineExtensions\Migrations\Tools\Cli\Tasks\GenerateTask')
            ->addTask('diff', 'DoctrineExtensions\Migrations\Tools\Cli\Tasks\DiffTask')
            ->addTask('status', 'DoctrineExtensions\Migrations\Tools\Cli\Tasks\StatusTask')
            ->addTask('execute', 'DoctrineExtensions\Migrations\Tools\Cli\Tasks\ExecuteTask');

    $cli->run($_SERVER['argv']);

You will see that you have a few new tasks when you execute your command line
with no arguments:

    $ ./doctrine
    Doctrine Command Line Interface

    Available Tasks:

     ...

    Migrations:diff --from=<FROM> --configuration=<PATH> --migrations-dir=<PATH> --version-table=<PATH>
      Generate migration classes by comparing your current database to your ORM mapping information.

    Migrations:execute --configuration=<PATH> --migrations-dir=<PATH> --version-table=<PATH> --version=<FROM> --write-sql=<PATH> --direction=<DIRECTION> --dry-run
      Execute a single migration version up or down

    Migrations:generate --migrations-dir=<PATH>
      Manually add and delete migration versions from the version table.

    Migrations:migrate --configuration=<PATH> --migrations-dir=<PATH> --version-table=<PATH> --version=<FROM> --write-sql=<PATH> --dry-run
      Execute a migration to a specified version or the current version.

    Migrations:status --configuration=<PATH> --migrations-dir=<PATH> --version-table=<PATH>
      View the status of some migrations.

    Migrations:version --configuration=<PATH> --add=<PATH> --delete=<FROM>
      Manually add and delete migration versions from the version table.

     ...

## Configuration

The last thing you need to do is to configure your migrations. You can do so
manually by always passing the _--migrations-dir_ and _--version-table_
options but you will save a lot of work when using a XML/YAML configuration file.
For this you can use the _--configuration_ option to manually specify the path
to a configuration file to use. If you don't specify any options the tasks will
look for a file named _configuration.xml_ or _configuration.yml_ at the root of
your command line. For the upcoming examples you can use a _configuration.xml_
file like the following:

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-migrations xmlns="http://doctrine-project.org/schemas/migrations/configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://doctrine-project.org/schemas/migrations/configuration
                        http://doctrine-project.org/schemas/migrations/configuration.xsd">

        <table name="doctrine_migration_versions" />

        <directories>
            <directory path="/path/to/migrations" />
        </directories>

    </doctrine-migrations>

You can also optionally specify each migration individually instead of reading
it from a directory. This offers more flexibility as the naming pattern is not
required since you are not reading anything from the filesystem.

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-migrations xmlns="http://doctrine-project.org/schemas/migrations/configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://doctrine-project.org/schemas/migrations/configuration
                        http://doctrine-project.org/schemas/migrations/configuration.xsd">

        <table name="doctrine_migration_versions" />

        <migrations>
            <migration version="1" class="DoctrineMigrations\NewMigration" />
        </migrations>

    </doctrine-migrations>

Of course you could do the same thing with a _configuration.yml_ file:

    table_name: doctrine_migration_versions
    directories:
      - /path/to/migrations

And if you want to specify each migration manually in YAML you can:

    table_name: doctrine_migration_versions
    migrations:
      migration1:
        version: 1
        class: DoctrineMigrations\NewMigration

## Migration Classes

As now everything is setup and configured you are ready to start writing
migration classes. You can easily generate your first migration class with the
following command:

    $ ./doctrine migrations:generate --migrations-dir=migrations/DoctrineMigrations/
    Doctrine Command Line Interface

    Writing new migration class to "migrations/DoctrineMigrations/Version20100323140330.php"

Have a look and you will see a new class at the above location that looks like
the following:

    <?php

    namespace DoctrineMigrations;

    use DoctrineExtensions\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100323140330 extends AbstractMigration
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
    Doctrine Command Line Interface

     == Overview

        >> Table Name:                                         doctrine_migration_versions
        >> Current Version:                                    0
        >> Latest Version:                                     2010-03-23 14:03:30 (20100323140330)
        >> Executed Migrations:                                0
        >> Available Migrations:                               1
        >> New Migrations:                                     1

     == Status

        >> 2010-03-23 14:03:30 (20100323140330)                not migrated

As you can see we have a new version present and it is ready to be executed. The
problem is it does not have anything in it so nothing would be executed! Let's
add some code to it and add a new table:

    <?php

    namespace DoctrineMigrations;

    use DoctrineExtensions\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100323140330 extends AbstractMigration
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
    Doctrine Command Line Interface

     == Current version is 0 ==

      ++ migrating 20100323140330

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

Everything looks good so we can remove the --dry-run option and actually execute
the migration:

    $ ./doctrine migrations:migrate
    Doctrine Command Line Interface

     == Current version is 0 ==

      ++ migrating 20100323140330

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

      ++ migrated

By checking the status again you will see everything is updated:

    $ ./doctrine migrations:status
    Doctrine Command Line Interface

     == Overview

        >> Table Name:                                         doctrine_migration_versions
        >> Current Version:                                    2010-03-23 14:03:30 (20100323140330)
        >> Latest Version:                                     2010-03-23 14:03:30 (20100323140330)
        >> Executed Migrations:                                1
        >> Available Migrations:                               1
        >> New Migrations:                                     0

     == Status

        >> 2010-03-23 14:03:30 (20100323140330)                migrated

## Manual SQL Migrations

Sometimes you need to do some complex migration operations which force you
to use plain SQL statements. Using the __addSql()_ this is possible within any
migration class.

First you need to generate a new migration class:

    $ ./doctrine migrations:generate --migrations-dir=migrations/DoctrineMigrations/
    Doctrine Command Line Interface

    Writing new migration class to "migrations/DoctrineMigrations/Version20100323160310.php"

This newly generated migration class is the place where you can add your own
custom SQL queries:

    <?php

    namespace DoctrineMigrations;

    use DoctrineExtensions\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100323160310 extends AbstractMigration
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
    Doctrine Command Line Interface

     == Current version is 20100323140330 == 

      ++ migrating 20100323160310

         -> CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB

      ++ migrated

## Reverting Migrations

As you noticed above we defined a down() method that drops the users table that
we created. This allows us to easily revert the migrations.

    $ ./doctrine migrations:migrate --version=0
    Doctrine Command Line Interface

     == Current version is 20100323160310 ==

      -- reverting 20100323160310

         -> DROP TABLE addresses

      -- reverted

      -- reverting 20100323140330

         -> DROP TABLE users

      -- reverted

Now our database is back to where we originally started. Give it a check with
the status command:

    $ ./doctrine migrations:status
    Doctrine Command Line Interface

     == Overview

        >> Table Name:                                         doctrine_migration_versions
        >> Current Version:                                    0
        >> Latest Version:                                     2010-03-23 16:03:10 (20100323160310)
        >> Executed Migrations:                                0
        >> Available Migrations:                               2
        >> New Migrations:                                     2

     == Status

        >> 2010-03-23 14:03:30 (20100323140330)                not migrated
        >> 2010-03-23 16:03:10 (20100323160310)                not migrated

## Writing Migration SQL Files

You can optionally choose to not execute a migration directly on a database and
instead output all the SQL statements to a file. This is possible with the
following command:

    $ ./doctrine migrations:migrate --write-sql=migration.sql
    Doctrine Command Line Interface

     == Current version is 0 ==

      ++ migrating 20100323140330

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

     == Writing migration file to "migration.sql" ==

Now if you have a look at migration.sql you will see the would be executed SQL
outputted in a nice format:

    # Doctrine Migration File Generated on 2010-03-23 15:03:56
    # Migrating from 0 to 20100323140330

    # Version 20100323140330
    CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB;

## Managing the Version Table

Sometimes you may need to manually change something in the database table which
manages the versions for some migrations. For this you can use the version task.
Take a look at the tasks available options:

    $ ./doctrine migrations:version --configuration=<PATH> --add=<YYYYMMDDHHMMSS> --delete=<YYYYMMDDHHMMSS>

The migrations version table stores each version that has been migrated and 
exists in the current schema. Sometimes you may need to manually fix something
in the database and manually add the migration version to the database.

    $ ./doctrine migrations:version --configuration=migrations.xml --add=YYYYMMDDHHMMSS

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

Instead of manually editing the entity class when you change your mapping
information you can have Doctrine generate the new property and method stubs
for you by running the generate-entities task:

    $ ./doctrine orm:generate-entities --from=yaml --dest=Entities

Now if you run the diff task you will get a nicely generated migration with the
changes required to update your database!

    $ ./doctrine migrations:diff --from=yaml --trace --migrations-dir=migrations/DoctrineMigrations/
    Doctrine Command Line Interface

    Writing new migration class to "migrations/DoctrineMigrations/Version20100323160341.php"

The migration class that is generated contains the SQL statements required to 
update your database:

    <?php

     namespace DoctrineMigrations;

     use DoctrineExtensions\Migrations\AbstractMigration,
         Doctrine\DBAL\Schema\Schema;

     class Version20100323160341 extends AbstractMigration
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
