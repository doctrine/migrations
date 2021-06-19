Implicit commits
================

Since PHP8, if you are using some platforms with some drivers such as
MySQL with PDO, you may get an error that you did not get before when
using this library: ``There is no active transaction``. It comes from
the fact that some platforms like MySQL or Oracle do not support DDL
statements (``CREATE TABLE``, ``ALTER TABLE``, etc.) in transactions.

The issue existed before PHP 8 but is now made visible by e.g. PDO,
which now produces the above error message when this library attempts to
commit a transaction that has already been commited before.

Consider the following migration.

.. code-block:: php

    public function up(Schema $schema): void
    {
        $users = [
            ['name' => 'mike', 'id' => 1],
            ['name' => 'jwage', 'id' => 2],
            ['name' => 'ocramius', 'id' => 3],
        ];

        foreach ($users as $user) {
            $this->addSql('UPDATE user SET happy = true WHERE name = :name AND id = :id', $user);
        }

        $this->addSql('CREATE TABLE example_table (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
    }

When you run that migration, what actually happens with some platforms
is you get the updates inside an implicitly commited transaction, then
the ``CREATE TABLE`` happens outside that transaction, and then there is
an attempt to commit an non-existent transaction.

In that sort of situation, if you still wish to get the DML statements
inside a transaction, we recommend you split the migration in 2
migrations, as follows.

.. code-block:: php

    final class Version20210401193057 extends AbstractMigration
    {
        public function up(Schema $schema): void
        {
            $users = [
                ['name' => 'mike', 'id' => 1],
                ['name' => 'jwage', 'id' => 2],
                ['name' => 'ocramius', 'id' => 3],
            ];

            foreach ($users as $user) {
                $this->addSql('UPDATE user SET happy = true WHERE name = :name AND id = :id', $user);
            }
        }
    }

    final class Version20210401193058 extends AbstractMigration
    {
        public function up(Schema $schema): void
        {
            $this->addSql('CREATE TABLE example_table (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        }

        public function isTransactional(): bool
        {
            return false;
        }
    }

Please refer to the manual of your database platform to know if you need
to do this or not.

At the moment, this library checks if there is an active transaction
before commiting it, which means you should not encouter the error
described above. It will not be the case in the next major version
though, and you should prepare for that.

To help you deal with this issue, the library features a configuration
key called ``transactional``. Setting it to ``false`` will cause new
migrations to be generated with the override method above, making new
migrations non-transactional by default.
