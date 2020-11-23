<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\ORM\EntityManagerInterface;

final class ExistingEntityManager implements EntityManagerLoader
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
