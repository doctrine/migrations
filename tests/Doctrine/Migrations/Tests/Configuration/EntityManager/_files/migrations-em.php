<?php

declare(strict_types=1);

use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

$conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

$conf = new Configuration();
$conf->setProxyDir(__DIR__);
$conf->setProxyNamespace('Foo');
$conf->setMetadataDriverImpl(new PHPDriver(''));

return EntityManager::create($conn, $conf);
