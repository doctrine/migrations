<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\Migrations\Configuration\Exception\InvalidLoader;
use Doctrine\ORM\EntityManagerInterface;

final class ExistingEntityManager implements EntityManagerLoader
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getEntityManager(string|null $name = null): EntityManagerInterface
    {
        if ($name !== null) {
            throw InvalidLoader::noMultipleEntityManagers($this);
        }

        return $this->entityManager;
    }
}
