<?php

namespace Doctrine\Migrations\DBAL;

use Doctrine\Migrations\Migrations;
use Doctrine\Migrations\Configuration;
use Doctrine\Migrations\Loader\ChainLoader;
use Doctrine\Migrations\Executor\ExecutorRegistry;
use Doctrine\DBAL\DriverManager;

class Factory
{
    public function createFromIniFile($fileName)
    {
        $data = parse_ini_file($fileName, true);
        return $this->createFromArray($data);
    }

    public function createFromArray($data)
    {
        $configuration = new Configuration();

        if (isset($data['migrations']['allow_init_on_migrate'])) {
            $configuration->setAllowInitOnMigrate((bool)$data['migrations']['allow_init_on_migrate']);
        }

        if (isset($data['migrations']['validate_on_migrate'])) {
            $configuration->setValidateOnMigrate((bool)$data['migrations']['validate_on_migrate']);
        }

        if (isset($data['migrations']['script_directory'])) {
            $configuration->setScriptDirectory($data['migrations']['script_directory']);
        }

        if (isset($data['migrations']['allow_out_of_order_migrations'])) {
            $configuration->setOutOfOrderMigrationsAllowed((bool)$data['migrations']['allow_out_of_order_migrations']);
        }

        if ( ! isset($data['db'])) {
            throw new \RuntimeException('Section "migrations.db" is missing in configuration.');
        }

        $connection = DriverManager::getConnection($data['db']);

        $chainLoader = new ChainLoader();
        $chainLoader->add(new Loader\SqlFileLoader());
        $chainLoader->add(new Loader\PhpFileLoader());

        $executorRegistry = new ExecutorRegistry();
        $executorRegistry->add(new Executor\SqlFileExecutor($connection));
        $executorRegistry->add(new Executor\PhpFileExecutor($connection));

        return new Migrations(
            $configuration,
            new TableMetadataStorage($connection, $configuration->getScriptDirectory()),
            $chainLoader,
            $executorRegistry
        );
    }
}
