<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Query;

use Doctrine\Migrations\Query\Exception\InvalidArguments;
use Stringable;

use function count;

/**
 * The Query wraps the sql query, parameters and types.
 */
final class Query implements Stringable
{
    /**
     * @param mixed[] $parameters
     * @param mixed[] $types
     */
    public function __construct(
        private readonly string $statement,
        private readonly array $parameters = [],
        private readonly array $types = [],
    ) {
        if (count($types) > count($parameters)) {
            throw InvalidArguments::wrongTypesArgumentCount($statement, count($parameters), count($types));
        }
    }

    public function __toString(): string
    {
        return $this->statement;
    }

    public function getStatement(): string
    {
        return $this->statement;
    }

    /** @return mixed[] */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /** @return mixed[] */
    public function getTypes(): array
    {
        return $this->types;
    }
}
