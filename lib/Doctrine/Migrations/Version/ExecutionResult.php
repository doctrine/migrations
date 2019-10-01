<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;
use function count;

/**
 * The ExecutionResult class is responsible for storing the result of a migration version after it executes.
 *
 * @internal
 */
final class ExecutionResult
{
    /** @var string[] */
    private $sql = [];

    /** @var mixed[] */
    private $params = [];

    /** @var mixed[] */
    private $types = [];

    /** @var float|null */
    private $time;

    /** @var float|null */
    private $memory;

    /** @var bool */
    private $skipped = false;

    /** @var bool */
    private $error = false;

    /** @var Throwable|null */
    private $exception;

    /** @var DateTimeImmutable|null */
    private $executedAt;

    /** @var int */
    private $state;

    /** @var Schema|null */
    private $toSchema;

    /** @var Version */
    private $version;

    /** @var string */
    private $direction;

    public function __construct(Version $version, string $direction = Direction::UP, ?DateTimeImmutable $executedAt = null)
    {
        $this->executedAt = $executedAt;
        $this->version    = $version;
        $this->direction  = $direction;
    }

    public function getDirection() : string
    {
        return $this->direction;
    }

    public function getExecutedAt() : ?DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function setExecutedAt(DateTimeImmutable $executedAt) : void
    {
        $this->executedAt = $executedAt;
    }

    public function getVersion() : Version
    {
        return $this->version;
    }

    public function hasSql() : bool
    {
        return count($this->sql) !== 0;
    }

    /**
     * @return string[]
     */
    public function getSql() : array
    {
        return $this->sql;
    }

    /**
     * @param string[] $sql
     * @param mixed[]  $params
     * @param int[]    $types
     */
    public function setSql(array $sql, array $params = [], array $types = []) : void
    {
        $this->sql    = $sql;
        $this->params = $params;
        $this->types  = $types;
    }

    /**
     * @return mixed[]
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * @return mixed[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }

    public function getTime() : ?float
    {
        return $this->time;
    }

    public function setTime(float $time) : void
    {
        $this->time = $time;
    }

    public function getMemory() : ?float
    {
        return $this->memory;
    }

    public function setMemory(float $memory) : void
    {
        $this->memory = $memory;
    }

    public function setSkipped(bool $skipped) : void
    {
        $this->skipped = $skipped;
    }

    public function isSkipped() : bool
    {
        return $this->skipped;
    }

    public function setError(bool $error, ?Throwable $exception = null) : void
    {
        $this->error     = $error;
        $this->exception = $exception;
    }

    public function hasError() : bool
    {
        return $this->error;
    }

    public function getException() : ?Throwable
    {
        return $this->exception;
    }

    public function setToSchema(Schema $toSchema) : void
    {
        $this->toSchema = $toSchema;
    }

    public function getToSchema() : Schema
    {
        if ($this->toSchema === null) {
            throw new RuntimeException('Cannot call getToSchema() when toSchema is null.');
        }

        return $this->toSchema;
    }

    public function getState() : int
    {
        return $this->state;
    }

    public function setState(int $state) : void
    {
        $this->state = $state;
    }
}
