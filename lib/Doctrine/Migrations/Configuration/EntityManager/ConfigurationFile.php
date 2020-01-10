<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\Migrations\Configuration\EntityManager\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\EntityManager\Exception\InvalidConfiguration;
use Doctrine\ORM\EntityManager;
use function file_exists;

/**
 * The ConfigurationFileLoader class is responsible for loading a Doctrine\DBAL\EntityManager from a PHP file
 * that returns an array of EntityManager information which is used to instantiate a EntityManager with DriverManager::getConnection()
 */
final class ConfigurationFile implements EntityManagerLoader
{
    /** @var string */
    private $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     *
     * @throws InvalidConfiguration
     */
    public function getEntityManager() : EntityManager
    {
        if (! file_exists($this->filename)) {
            throw FileNotFound::new($this->filename);
        }

        $params = include $this->filename;

        if ($params instanceof EntityManager) {
            return $params;
        }

        if ($params instanceof EntityManagerLoader) {
            return $params->getEntityManager();
        }

        throw InvalidConfiguration::invalidArrayConfiguration();
    }
}
