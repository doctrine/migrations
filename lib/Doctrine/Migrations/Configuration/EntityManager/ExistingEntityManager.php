<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\ORM\EntityManager;

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
