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
    private string $statement;

    /** @var mixed[] */
    private array $parameters;

    /** @var mixed[] */
    private array $types;

    private bool $executeAsStatement;

    /**
     * @param mixed[] $parameters
     * @param mixed[] $types
     */
    public function __construct(string $statement, array $parameters = [], array $types = [], bool $executeAsStatement = false)
    {
        if (count($types) > count($parameters)) {
            throw InvalidArguments::wrongTypesArgumentCount($statement, count($parameters), count($types));
        }

        $this->statement          = $statement;
        $this->parameters         = $parameters;
        $this->types              = $types;
        $this->executeAsStatement = $executeAsStatement;
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

    public function getExecuteAsStatement(): bool
    {
        return $this->executeAsStatement;
    }
}
