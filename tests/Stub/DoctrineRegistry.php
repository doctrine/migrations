<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\Proxy;
use Exception;

use function array_combine;
use function array_keys;

class DoctrineRegistry extends AbstractManagerRegistry
{
    /**
     * @param array<string,Connection>    $connections
     * @param array<string,EntityManager> $realEntityManagers
     */
    public function __construct(
        private readonly array $connections = [],
        private readonly array $realEntityManagers = [],
    ) {
        $connectionNames        = array_keys($connections);
        $realEntityManagerNames = array_keys($realEntityManagers);

        parent::__construct(
            'some_registry',
            array_combine($connectionNames, $connectionNames),
            array_combine($realEntityManagerNames, $realEntityManagerNames),
            $connectionNames[0] ?? 'default',
            $realEntityManagerNames[0] ?? 'default',
            Proxy::class,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getService($name): object
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
     * To be removed when support for Persistence 2 is dropped.
     *
     * @param string $alias
     */
    public function getAliasNamespace($alias): string
    {
        throw new Exception('Not Implemented');
    }
}
