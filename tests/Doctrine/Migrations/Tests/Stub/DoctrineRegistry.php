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
    private array $realEntityManagers;

    /** @var Connection[] */
    private array $connections;

    /**
     * @param array<string,Connection>    $connections
     * @param array<string,EntityManager> $realEntityManagers
     */
    public function __construct(array $connections = [], array $realEntityManagers = [])
    {
        /**
         * @var string[] $connectionNames
         */
        $connectionNames = array_keys($connections);
        /**
         * @var string[] $realEntityManagerNames
         */
        $realEntityManagerNames = array_keys($realEntityManagers);
        parent::__construct(
            'some_registry',
            array_combine($connectionNames, $connectionNames),
            array_combine($realEntityManagerNames, $realEntityManagerNames),
            key($connections) ?? null,
            key($realEntityManagers) ?? null,
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
    protected function resetService($name): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getAliasNamespace($alias): string
    {
        throw new Exception('Not Implemented');
    }
}
