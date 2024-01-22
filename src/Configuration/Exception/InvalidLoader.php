<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use Doctrine\Migrations\Configuration\Connection\ConnectionLoader;
use Doctrine\Migrations\Configuration\EntityManager\EntityManagerLoader;
use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

final class InvalidLoader extends InvalidArgumentException implements ConfigurationException
{
    public static function noMultipleConnections(ConnectionLoader $loader): self
    {
        return new self(sprintf(
            'Only one connection is supported by %s',
            get_debug_type($loader),
        ));
    }

    public static function noMultipleEntityManagers(EntityManagerLoader $loader): self
    {
        return new self(sprintf(
            'Only one entity manager is supported by %s',
            get_debug_type($loader),
        ));
    }
}
