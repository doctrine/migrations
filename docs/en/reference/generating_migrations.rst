Generating Migrations
=====================

Migrations can be created for you if you're using the Doctrine 2 ORM or the DBAL
`Schema Representation <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html>`_.
Empty migration classes can also be created.

Favor the tools described here over manually created migration files as the library
has some :doc:`requirements around migration version numbers <version_numbers>`.

Using the ORM
-------------

If you are using the Doctrine 2 ORM you can easily generate a migration class
by modifying your mapping information and running the diff task to compare it
to your current database schema.

If you are using the sandbox you can modify the provided `yaml/Entities.User.dcm.yml`
and add a new column:

.. code-block:: yaml

    Entities\User:
      # ...
      fields:
        # ...
        test:
          type: string
          length: 255
      # ...

Be sure that you add the property to the `Entities/User.php` file:

.. code-block:: php

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

.. code-block:: bash

    $ ./doctrine migrations:diff
    Generated new migration class to "/path/to/migrations/DoctrineMigrations/Version20100416130459.php" from schema differences.

The migration class that is generated contains the SQL statements required to
update your database:

.. code-block:: php

    namespace DoctrineMigrations;

    use Doctrine\DBAL\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100416130459 extends AbstractMigration
    {
        public function up(Schema $schema)
        {
            $this->addSql('ALTER TABLE users ADD test VARCHAR(255) NOT NULL');
        }

        public function down(Schema $schema)
        {
            $this->addSql('ALTER TABLE users DROP test');
        }
    }

The SQL generated here is the exact same SQL that would be executed if you were
using the `orm:schema-tool` task and the `--update` option. This just allows you to
capture that SQL and maybe tweak it or add to it and trigger the deployment
later across multiple database servers.

Without the ORM
---------------

Internally the diff command generates a ``Doctrine\DBAL\Schema\Schema`` object
from your entity's metadata using an implementation of
``Doctrine\DBAL\Migrations\Provider\SchemaProvider``. To use the Schema representation
directly, without the ORM, you must implement this interface yourself.

.. code-block:: php

    <?php

    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\DBAL\Migrations\Provider\SchemaProvider;

    final class CustomSchemaProvider implements SchemaProvider
    {
        /**
         * The schema provider only has one method: `createSchema`. This should
         * return an Schema object that represents the state to which you'd like
         * to migrate your database.
         * {@inheritdoc}
         */
        public function createSchema()
        {
            $schema = new Schema();

            $table = $schema->createTable('foo');
            $table->addColumn('id', 'integer', array(
                'autoincrement' => true,
            ));
            $table->setPrimaryKey(array('id'));

            return $schema;
        }
    }

The ``StubSchemaProvider`` provided with the migrations library is another option.
It simply takes a schema object to its constructor and returns it from ``createSchema``.

.. code-block:: php

    <?php

    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\DBAL\Migrations\Provider\StubSchemaProvider;

    $schema = new Schema();

    $table = $schema->createTable('foo');
    $table->addColumn('id', 'integer', array(
        'autoincrement' => true,
    ));
    $table->setPrimaryKey(array('id'));

    $provider = new StubSchemaProvider($schema);
    $provider->createSchema() === $schema; // true

By default the ``doctrine-migrations`` command line tool will only add the diff
command if the ORM is present. Without the ORM, you'll have to add the diff command
to your `console application <http://symfony.com/doc/current/components/console/introduction.html>`_
manually, passing in your schema provider implementation to the diff command's constructor.

.. code-block:: php

    <?php

    use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;

    $schemaProvider = new CustomSchemaProvider();

    /** @var Symfony\Component\Console\Application */
    $app->add(new DiffCommand($schemaProvider));

    // ...

    $app->run();

With the custom provider in place the diff command will compare the current database
state to the one provided. If there's a mismatch, the differences will be put
into the generated migration just like the ORM examples above.

Ignoring Custom Tables
----------------------

If you have custom tables which are not managed by doctrine you might face the situation
that with every diff task you are executing you get the remove statements for those tables
added to the migration class.

Therefore you can configure doctrine with a schema filter.

.. code-block:: php

    $connection->getConfiguration()->setFilterSchemaAssetsExpression("~^(?!t_)~");

With this expression all tables prefixed with t_ will ignored by the schema tool.

If you use the DoctrineBundle with Symfony2 you can set the schema_filter option
in your configuration. You can find more information in the documentation of the
DoctrineMigationsBundle.

Creating Empty Migrations
-------------------------

Use the ``migrations:generate`` command to create an empty migration class.

.. code-block:: bash

    $ ./doctrine migrations:generate
    Generated new migration class to /path/to/migrations/DoctrineMigrations/Version20180107080000.php
