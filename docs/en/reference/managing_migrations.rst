.. index::
   single: Managing Migrations

Managing Migrations
===================

Now that we have a new migration class present, lets run the status task to see
if it is there:

.. code-block:: bash

    $ ./doctrine migrations:status

     == Configuration

        >> Name:                                               Doctrine Sandbox Migrations
        >> Database Driver:                                    pdo_mysql
        >> Database Name:                                      testdb
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

.. code-block:: php

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

.. code-block:: bash

    $ ./doctrine migrations:migrate --dry-run
    Are you sure you wish to continue?
    y
    Executing dry run of migration up to 20100416130452 from 0

      >> migrating 20100416130452

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

Everything looks good so we can remove the *--dry-run* option and actually execute
the migration:

.. code-block:: bash

    $ ./doctrine migrations:migrate
    Are you sure you wish to continue?
    y
    Migrating up to 20100416130452 from 0

      >> migrating 20100416130452

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

      >> migrated

Alternately, if you wish to run the migrations in an unattended mode, we can add the *--no--interaction* option and then
execute the migrations without any extra prompting from Doctrine.

.. code-block:: bash

    $ ./doctrine migrations:migrate --no-interaction
    Migrating up to 20100416130452 from 0

      >> migrating 20100416130452

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

      >> migrated

By checking the status again after using either method you will see everything is updated:

.. code-block:: bash

    $ ./doctrine migrations:status

     == Configuration

        >> Name:                                               Doctrine Sandbox Migrations
        >> Database Driver:                                    pdo_mysql
        >> Database Name:                                      testdb
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

Reverting Migrations
--------------------

You maybe noticed in the last example that we defined a *down()* method which
drops the users table that we created. This method allows us to easily revert
changes the schema has been migrated to. The *migrate* command takes a *version*
argument which you can use to roll back your schema to a specific version of
your migrations:

.. code-block:: bash

    $ ./doctrine migrations:migrate first
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

.. code-block:: bash

    $ ./doctrine migrations:status

     == Configuration

        >> Name:                                               Doctrine Sandbox Migrations
        >> Database Driver:                                    pdo_mysql
        >> Database Name:                                      testdb
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

Aliases
-------

There are some shortcut for convenience (first, prev, next, latest).
So that you don't have to know the name of the migration.
You can just call

.. code-block:: bash

    $ ./doctrine migrations:migrate prev

Writing Migration SQL Files
---------------------------

You can optionally choose to not execute a migration directly on a database and
instead output all the SQL statements to a file. This is possible by using the
*--write-sql* option of the *migrate* command:

.. code-block:: bash

    $ ./doctrine migrations:migrate --write-sql
    Executing dry run of migration up to 20100416130422 from 0

      >> migrating 20100416130401

         -> CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB

      >> migrating 20100416130422

         -> CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB

    Writing migration file to "/path/to/sandbox/doctrine_migration_20100416130405.sql"

Now if you have a look at the *doctrine_migration_20100416130405.sql* file you will see the would be
executed SQL outputted in a nice format:

.. code-block:: bash

    # Doctrine Migration File Generated on 2010-04-16 13:04:05
    # Migrating from 0 to 20100416130422

    # Version 20100416130401
    CREATE TABLE users (username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL) ENGINE = InnoDB;

    # Version 20100416130422
    CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;

.. _managing-versions-table:

Managing the Version Table
--------------------------

Sometimes you may need to manually change something in the database table which
manages the versions for some migrations. For this you can use the version task.
You can easily add a version like this:

.. code-block:: bash

    $ ./doctrine migrations:version YYYYMMDDHHMMSS --add

Or you can delete that version:

.. code-block:: bash

    $ ./doctrine migrations:version YYYYMMDDHHMMSS --delete

The command does not execute any migrations code, it simply adds the specified
version to the database.
