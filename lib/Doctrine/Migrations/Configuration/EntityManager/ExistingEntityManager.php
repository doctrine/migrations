<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\ORM\EntityManager;

/**
 * The ConfigurationFileLoader class is responsible for loading a Doctrine\DBAL\EntityManager from a PHP file
 * that returns an array of EntityManager information which is used to instantiate a EntityManager with DriverManager::getEntityManager()
 */
final class ExistingEntityManager implements EntityManagerLoader
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager() : EntityManager
    {
        return $this->entityManager;
    }
}
