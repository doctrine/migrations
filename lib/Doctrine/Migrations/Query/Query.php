<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Query;

use Doctrine\Migrations\Query\Exception\InvalidArguments;

use function count;

/**
 * The Query wraps the sql query, parameters and types.
 */
final class Query
{
    /** @var string */
    private $statement;

    /** @var mixed[] */
    private $parameters;

    /** @var mixed[] */
    private $types;

    /**
     * @param mixed[] $parameters
     * @param mixed[] $types
     */
    public function __construct(string $statement, array $parameters = [], array $types = [])
    {
        if (count($types) > count($parameters)) {
            throw InvalidArguments::wrongTypesArgumentCount($statement, count($parameters), count($types));
        }

        $this->statement  = $statement;
        $this->parameters = $parameters;
        $this->types      = $types;
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
