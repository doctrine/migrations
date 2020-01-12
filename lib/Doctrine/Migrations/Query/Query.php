<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Query;

/**
 * The Query wraps the sql query, parameters and types. It used in MigrationsQueryEventArgs.
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
        $this->statement  = $statement;
        $this->parameters = $parameters;
        $this->types      = $types;
    }

    public function getStatement() : string
    {
        return $this->statement;
    }

    /** @return mixed[] */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /** @return mixed[] */
    public function getTypes() : array
    {
        return $this->types;
    }
}
