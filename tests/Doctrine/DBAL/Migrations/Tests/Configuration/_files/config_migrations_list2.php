<?php
return [
'name'                 => 'Doctrine Sandbox Migrations',
'table_name'           => 'doctrine_migration_versions_test',
'migrations'           => [
    'migration1' =>
        [
            'class' => 'Doctrine\\DBAL\\Migrations\\Tests\\Stub\\Version1Test',
            'version' => 'Version1Test',
        ],
    'migration2' =>
        [
            'class' => 'Doctrine\\DBAL\\Migrations\\Tests\\Stub\\Version2Test',
            'version' => 'Version2Test',
        ],
    'migration3' =>
        [
            'class' => 'Doctrine\\DBAL\\Migrations\\Tests\\Stub\\Version3Test',
            'version' => 'Version3Test',
        ],
],
];
