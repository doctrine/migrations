<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager;

use Doctrine\ORM\EntityManager;

final class ConfiguredEntityManager implements EntityManagerLoader
{
    /** @var string */
    private $emConfig;

    public function __construct(string $emConfig = 'migrations-em.php')
    {
        $this->emConfig = $emConfig;
    }

    public function getEntityManager() : EntityManager
    {
        $loader = new ConfigurationFile(
            $this->emConfig
        );

        return $loader->getEntityManager();
    }
}
