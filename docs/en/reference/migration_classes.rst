Migration Classes
=================

As now everything is setup and configured you are ready to start writing
migration classes. You can easily generate your first migration class with the
following command:

.. code-block:: bash

    $ ./doctrine migrations:generate
    Generated new migration class to "/path/to/migrations/classes/DoctrineMigrations/Version20100416130401.php"

Have a look and you will see a new class at the above location that looks like
the following:

.. code-block:: php

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

You can now use the *addSql()* method within the up and down method.

Internally the addSql call are passed to the `dbal executeQuery method`_.
This means that you can use the power of the prepared statement easilly and that you don't need to copy paste the same
query with different parameters. You can just pass those differents parameters to the addSql method as parameters.

.. code-block:: php

        public function up(Schema $schema)
        {
            $users = array(
                    array('name' => 'mike', 'id' => 1),
                    array('name' => 'jwage', 'id' => 2),
                    array('name' => 'ocramius', 'id' => 3),
                    );
            foreach ($users as $user) {
                $this->addSql('UPDATE user SET happy = true WHERE name = :name AND id = :id', $user);
            }
        }

For more infos on `how the doctrine dbal executeQuery method works go tho the doctrine dbal documentation`_.

Additionally, there is also the preUp, postUp and preDown, postDown method, that are respectivelly called before and
 after the up and down method are called.

First you need to generate a new migration class:

.. code-block:: bash

    $ ./doctrine migrations:generate
    Generated new migration class to "/path/to/migrations/DoctrineMigrations/Version20100416130422.php"

This newly generated migration class is the place where you can add your own
custom SQL queries:

.. code-block:: php

    namespace DoctrineMigrations;

    use Doctrine\DBAL\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100416130422 extends AbstractMigration
    {
        public function up(Schema $schema)
        {
            $this->addSql('CREATE TABLE addresses (id INT NOT NULL, street VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB');
        }

        public function down(Schema $schema)
        {
            $this->addSql('DROP TABLE addresses');
        }
    }
