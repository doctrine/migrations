<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Symfony\Component\Console\Helper\HelperSet;

$conf = new Configuration();
$conf->setProxyDir(__DIR__);
$conf->setProxyNamespace('Foo');
$conf->setMetadataDriverImpl(new PHPDriver(''));

$conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

$em = new EntityManager($conn, $conf);

return new HelperSet([
    'em' => new EntityManagerHelper($em),
]);
