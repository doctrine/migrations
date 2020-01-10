<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager\Exception;

use InvalidArgumentException;

final class EntityManagerNotSpecified extends InvalidArgumentException implements LoaderException
{
    public static function new() : self
    {
        return new self(
            'You have to specify a --db-configuration file or pass a Database EntityManager as a dependency to the Migrations.'
        );
    }
}
