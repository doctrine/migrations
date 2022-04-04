<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\Migrations\Configuration\EntityManager\Exception\InvalidConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class ManagerRegistryEntityManager implements EntityManagerLoader
{
    private ManagerRegistry $registry;

    private ?string $defaultManagerName = null;

    public static function withSimpleDefault(ManagerRegistry $registry, ?string $managerName = null): self
    {
        $that                     = new self();
        $that->registry           = $registry;
        $that->defaultManagerName = $managerName;

        return $that;
    }

    private function __construct()
    {
    }

    public function getEntityManager(?string $name = null): EntityManagerInterface
    {
        $managerName = $name ?? $this->defaultManagerName;

        $em = $this->registry->getManager($managerName);
        if (! $em instanceof EntityManagerInterface) {
            throw InvalidConfiguration::invalidManagerType($em);
        }

        return $em;
    }
}
