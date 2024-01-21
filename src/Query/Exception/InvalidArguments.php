<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Query\Exception;

use Doctrine\Migrations\Exception\MigrationException;
use InvalidArgumentException;

use function sprintf;

class InvalidArguments extends InvalidArgumentException implements MigrationException
{
    public static function wrongTypesArgumentCount(string $statement, int $parameters, int $types): self
    {
        return new self(sprintf(
            'The number of types (%s) is higher than the number of passed parameters (%s) for the query "%s".',
            $types,
            $parameters,
            $statement,
        ));
    }
}
