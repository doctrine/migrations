<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\Migrations\Configuration\EntityManager\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\EntityManager\Exception\InvalidConfiguration;
use Doctrine\ORM\EntityManager;
use function file_exists;

/**
 * This class will return an EntityManager instance, loaded from a configuration file provided as argument.
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
