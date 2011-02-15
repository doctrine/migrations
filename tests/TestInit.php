<?php

require_once __DIR__ . '/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\DBAL\Migrations\Tests', __DIR__ . '/');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\DBAL\Migrations', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\Common', __DIR__ . '/../lib/vendor/doctrine-common/lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\DBAL', __DIR__ . '/../lib/vendor/doctrine-dbal/lib');
$classLoader->register();

$classLoader = new ClassLoader('Symfony\Component\Yaml', __DIR__ . '/../lib/vendor');
$classLoader->register();
