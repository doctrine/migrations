<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\ORM\EntityManagerInterface;

/**
 * The EntityManagerLoader defines the interface used to load the Doctrine\DBAL\EntityManager instance to use
 * for migrations.
 *
 * @internal
 */
interface EntityManagerLoader
{
    public function getEntityManager(?string $name = null): EntityManagerInterface;
}
