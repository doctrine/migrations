<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Query\Query;
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
    /** @var Query[] */
    private array $sql = [];

    /**
     * Seconds
     */
    private ?float $time = null;

    private ?float $memory = null;

    private bool $skipped = false;

    private bool $error = false;

    private ?Throwable $exception = null;

    private ?DateTimeImmutable $executedAt = null;

    private int $state;

    private ?Schema $toSchema = null;

    private Version $version;

    private string $direction;

    public function __construct(Version $version, string $direction = Direction::UP, ?DateTimeImmutable $executedAt = null)
    {
        $this->executedAt = $executedAt;
        $this->version    = $version;
        $this->direction  = $direction;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getExecutedAt(): ?DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function setExecutedAt(DateTimeImmutable $executedAt): void
    {
        $this->executedAt = $executedAt;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function hasSql(): bool
    {
        return count($this->sql) !== 0;
    }

    /**
     * @return Query[]
     */
    public function getSql(): array
    {
        return $this->sql;
    }

    /**
     * @param Query[] $sql
     */
    public function setSql(array $sql): void
    {
        $this->sql = $sql;
    }

    public function getTime(): ?float
    {
        return $this->time;
    }

    public function setTime(float $time): void
    {
        $this->time = $time;
    }

    public function getMemory(): ?float
    {
        return $this->memory;
    }

    public function setMemory(float $memory): void
    {
        $this->memory = $memory;
    }

    public function setSkipped(bool $skipped): void
    {
        $this->skipped = $skipped;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function setError(bool $error, ?Throwable $exception = null): void
    {
        $this->error     = $error;
        $this->exception = $exception;
    }

    public function hasError(): bool
    {
        return $this->error;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function setToSchema(Schema $toSchema): void
    {
        $this->toSchema = $toSchema;
    }

    public function getToSchema(): Schema
    {
        if ($this->toSchema === null) {
            throw new RuntimeException('Cannot call getToSchema() when toSchema is null.');
        }

        return $this->toSchema;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }
}
