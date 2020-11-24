<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\Migrations\Configuration\Exception\InvalidLoader;
use Doctrine\ORM\EntityManagerInterface;

final class ExistingEntityManager implements EntityManagerLoader
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager(?string $name = null): EntityManagerInterface
    {
        if ($name !== null) {
            throw InvalidLoader::noMultipleEntityManagers($this);
        }

        return $this->entityManager;
    }
}
