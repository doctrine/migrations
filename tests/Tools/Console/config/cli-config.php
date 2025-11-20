<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;

$conf = new Configuration();
$conf->setMetadataDriverImpl(new PHPDriver(''));

if (PHP_VERSION_ID > 80400) {
    $conf->enableNativeLazyObjects(true);
} else {
    $conf->setProxyDir(__DIR__);
    $conf->setProxyNamespace('Foo');
}

$conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

$em = new EntityManager($conn, $conf);

$config = new ConfigurationArray([
    'custom_template' => 'foo',
    'migrations_paths' => ['DoctrineMigrationsTest' => '.'],
]);

return DependencyFactory::fromEntityManager($config, new ExistingEntityManager($em));
