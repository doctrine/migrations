<?php

/**
 * Phing alternative to packaging the PHAR:
 *   $ php package.php
 *
 * @author Eric Clemmons <eric@smarterspam.com>
 */

chdir(dirname(dirname(dirname(__DIR__))));

$buildDir = realpath(dirname(__FILE__)) . '/build';

$pharName = "$buildDir/doctrine-migrations.phar";

if (!file_exists($buildDir)) {
    mkdir($buildDir);
}

if (file_exists($pharName)) {
    unlink($pharName);
}

$p = new Phar($pharName);
$p->CompressFiles(Phar::GZ);
$p->setSignatureAlgorithm(Phar::SHA1);

$p->startBuffering();

$dirs = array(
    'vendor/doctrine/migrations/lib'  =>  '/Doctrine/',
    'vendor/doctrine/dbal/lib'        =>  '/Doctrine/',
    'vendor/doctrine/common/lib'      =>  '/Doctrine/',
    'vendor/symfony/console'          =>  '/Symfony/'
);

foreach ($dirs as $dir => $filter) {
    $p->buildFromDirectory($dir, $filter);
}

$p->stopBuffering();

$p->setStub(file_get_contents(__DIR__ . '/phar-cli-stub.php'));
