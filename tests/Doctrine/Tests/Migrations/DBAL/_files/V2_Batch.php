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
