<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Throwable;
use function count;

/**
 * @var internal
 */
class VersionExecutionResult
{
    /** @var string[] */
    private $sql = [];

    /** @var mixed[] */
    private $params = [];

    /** @var mixed[] */
    private $types = [];

    /** @var null|float */
    private $time;

    /** @var bool */
    private $skipped = false;

    /** @var bool */
    private $error = false;

    /** @var null|Throwable */
    private $exception;

    /**
     * @param string[] $sql
     * @param mixed[]  $params
     * @param mixed[]  $types
     */
    public function __construct(array $sql = [], array $params = [], array $types = [])
    {
        $this->sql    = $sql;
        $this->params = $params;
        $this->types  = $types;
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
     */
    public function setSql(array $sql) : void
    {
        $this->sql = $sql;
    }

    /**
     * @return mixed[]
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * @param mixed[] $params
     */
    public function setParams(array $params) : void
    {
        $this->params = $params;
    }

    /**
     * @return mixed[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }

    /**
     * @param mixed[] $types
     */
    public function setTypes(array $types) : void
    {
        $this->types = $types;
    }

    public function getTime() : ?float
    {
        return $this->time;
    }

    public function setTime(float $time) : void
    {
        $this->time = $time;
    }

    public function setSkipped(bool $skipped) : void
    {
        $this->skipped = $skipped;
    }

    public function isSkipped() : bool
    {
        return $this->skipped;
    }

    public function setError(bool $error) : void
    {
        $this->error = $error;
    }

    public function hasError() : bool
    {
        return $this->error;
    }

    public function setException(Throwable $exception) : void
    {
        $this->exception = $exception;
    }

    public function getException() : ?Throwable
    {
        return $this->exception;
    }
}
