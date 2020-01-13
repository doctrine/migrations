<?php

declare(strict_types=1);

use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Configuration\ConfigurationArray;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

$conf = new Configuration();
$conf->setProxyDir(__DIR__);
$conf->setProxyNamespace('Foo');
$conf->setMetadataDriverImpl(new PHPDriver(''));

$conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

$em = EntityManager::create($conn, $conf);

$config = new ConfigurationArray([
    'name' => 'Doctrine Sandbox Migrations Cli',
    'migrations_paths' => ['DoctrineMigrationsTest' => '.'],
]);

return DependencyFactory::fromEntityManager($config, new ExistingEntityManager($em));
