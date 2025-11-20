<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\EntityManager\EntityManagerLoader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;

return new class () implements EntityManagerLoader {
    public function getEntityManager(string|null $name = null): EntityManagerInterface
    {
        $conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $conf = new Configuration();
        $conf->setMetadataDriverImpl(new PHPDriver(''));

        if (PHP_VERSION_ID > 80400) {
            $conf->enableNativeLazyObjects(true);
        } else {
            $conf->setProxyDir(__DIR__);
            $conf->setProxyNamespace('Foo');
        }

        return new EntityManager($conn, $conf);
    }
};
