.. index::
   single: Installation

Installation
============

You have two options, you can either use composer to install doctrine migrations or you can use the standalone Phar package.

Composer
~~~~~~~~

Install it with composer:

.. code-block:: sh

    composer require doctrine/migrations


PHP Binary / PHAR
~~~~~~~~~~~~~~~~~

You can download the Migrations PHP Binary, which is a standalone PHAR package
file with all the required dependencies. You can drop that single file onto any server
and start using the Doctrine Migrations.

To register a system command for the migrations you can create a simple batch
script, for example on a \*nix Environment creating a `/usr/local/bin/doctrine-migrations`:

.. code-block:: bash

    #!/bin/sh
    php /path/to/doctrine-migrations.phar "$@"

You could now go and use the migrations like:

.. code-block:: bash

    [shell]
    myshell> doctrine-migrations

Because the PHAR file is standalone it does not rely on the Symfony Console 'db' Helper,
but you have to pass a `--db-configuration` parameter that points to a PHP file
which returns the parameters for `Doctrine\DBAL\DriverManager::getConnection($dbParams)`.
If you don't specify this option Doctrine Migrations will look for a `migrations-db.php`
file returning that parameters in your current directory and only throw an error if
that is not found.

Configuration
-------------

The last thing you need to do is to configure your migrations. You can do so
by using the *--configuration* option to manually specify the path
to a configuration file. If you don't specify any configuration file the tasks will
look for a file named *migrations.xml* or *migrations.yml* at the root of
your command line. For the upcoming examples you can use a *migrations.xml*
file like the following:

.. code-block:: xml

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-migrations xmlns="http://doctrine-project.org/schemas/migrations/configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://doctrine-project.org/schemas/migrations/configuration
                        http://doctrine-project.org/schemas/migrations/configuration.xsd">

        <name>Doctrine Sandbox Migrations</name>

        <migrations-namespace>DoctrineMigrations</migrations-namespace>

        <table name="doctrine_migration_versions" />

        <migrations-directory>/path/to/migrations/classes/DoctrineMigrations</migrations-directory>

    </doctrine-migrations>

Of course you could do the same thing with a *configuration.yml* file:

.. code-block:: yaml

    name: Doctrine Sandbox Migrations
    migrations_namespace: DoctrineMigrations
    table_name: doctrine_migration_versions
    migrations_directory: /path/to/migrations/classes/DoctrineMigrations

With the above example, the migrations tool will search the ``migrations_directory``
recursively for files that begin with ``Version`` followed one to 255 characters
and a ``.php`` suffix. ``Version.{1,255}\.php`` is the regular expression that's
used.

Everything after ``Version`` will be treated as the actual version in
the database. Take the file name ``VersionSomeVersion.php``, ``SomeVersion`` would
be the version *number* stored in the migrations database table. Since versions
are ordered, doctrine :doc:`generates </reference/generating_migrations>` version
numbers with a date time like ``Version20150505120000.php``. This ensures that
the migrations are executed in the correct order.

While you *can* use custom filenames, it's probably a good idea to the Doctrine
:doc:`generate migration files </reference/generating_migrations>` for you.


And if you want to specify each migration manually in YAML you can:

.. code-block:: yaml

    table_name: doctrine_migration_versions
    migrations_directory: /path/to/migrations/classes/DoctrineMigrations
    migrations:
      migration1:
        version: 20100704000000
        class: DoctrineMigrations\NewMigration

If you specify your own migration classes (like `DoctrineMigrations\NewMigration` in the previous
example) you will need an autoloader unless all those classes begin with the prefix Version*,
for example path/to/migrations/classes/VersionNewMigration.php.
