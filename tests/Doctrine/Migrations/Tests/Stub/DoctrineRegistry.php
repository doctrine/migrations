<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\AbstractManagerRegistry;
use Exception;
use function array_combine;
use function array_keys;
use function key;

class DoctrineRegistry extends AbstractManagerRegistry
{
    /** @var EntityManager[] */
    private $realEntityManagers;

    /** @var Connection[] */
    private $connections;

    /**
     * @param Connection[]    $connections
     * @param EntityManager[] $realEntityManagers
     */
    public function __construct(array $connections = [], array $realEntityManagers = [])
    {
        parent::__construct(
            'some_registry',
            (array) array_combine(array_keys($connections), array_keys($connections)),
            (array) array_combine(array_keys($realEntityManagers), array_keys($realEntityManagers)),
            key($connections) !== null ? (string) key($connections): null,
            key($realEntityManagers) !== null ? (string) key($realEntityManagers) : null,
            'Doctrine\Persistence\Proxy'
        );
        $this->realEntityManagers = $realEntityManagers;
        $this->connections        = $connections;
    }

    /**
     * {@inheritDoc}
     */
    protected function getService($name)
    {
        return $this->realEntityManagers[$name] ?? $this->connections[$name];
    }

    /**
     * {@inheritDoc}
     */
    protected function resetService($name) : void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getAliasNamespace($alias) : string
    {
        throw new Exception('Not Implemented');
    }
}
