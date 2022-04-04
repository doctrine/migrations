<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExecutionResultTest extends TestCase
{
    private ExecutionResult $versionExecutionResult;

    public function testHasSql(): void
    {
        self::assertTrue($this->versionExecutionResult->hasSql());
    }

    public function testGetSetSql(): void
    {
         $queries = $this->versionExecutionResult->getSql();
        self::assertCount(1, $queries);
        self::assertSame('SELECT 1', $queries[0]->getStatement());
        self::assertSame([1], $queries[0]->getParameters());
        self::assertSame([2], $queries[0]->getTypes());

        $this->versionExecutionResult->setSql([new Query('SELECT 2', [2], [3])]);

        $queries = $this->versionExecutionResult->getSql();
        self::assertCount(1, $queries);
        self::assertSame('SELECT 2', $queries[0]->getStatement());
        self::assertSame([2], $queries[0]->getParameters());
        self::assertSame([3], $queries[0]->getTypes());
    }

    public function testGetSetTime(): void
    {
        self::assertNull($this->versionExecutionResult->getTime());

        $this->versionExecutionResult->setTime(5.5);

        self::assertSame(5.5, $this->versionExecutionResult->getTime());
    }

    public function testGetSetMemory(): void
    {
        self::assertNull($this->versionExecutionResult->getMemory());

        $this->versionExecutionResult->setMemory(555555.0);

        self::assertSame(555555.0, $this->versionExecutionResult->getMemory());
    }

    public function testSkipped(): void
    {
        self::assertFalse($this->versionExecutionResult->isSkipped());

        $this->versionExecutionResult->setSkipped(true);

        self::assertTrue($this->versionExecutionResult->isSkipped());
    }

    public function testError(): void
    {
        self::assertFalse($this->versionExecutionResult->hasError());

        $this->versionExecutionResult->setError(true);

        self::assertTrue($this->versionExecutionResult->hasError());
    }

    public function testExceptionNull(): void
    {
        self::assertNull($this->versionExecutionResult->getException());
    }

    public function testExecutedAt(): void
    {
        $date = new DateTimeImmutable();
        $this->versionExecutionResult->setExecutedAt($date);

        self::assertSame($date, $this->versionExecutionResult->getExecutedAt());
    }

    public function testGetVersion(): void
    {
        self::assertSame('foo', (string) $this->versionExecutionResult->getVersion());
    }

    public function testException(): void
    {
        $exception = new InvalidArgumentException();

        $this->versionExecutionResult->setError(true, $exception);

        self::assertSame($exception, $this->versionExecutionResult->getException());
    }

    public function testToSchema(): void
    {
        $toSchema = $this->createMock(Schema::class);

        $this->versionExecutionResult->setToSchema($toSchema);

        self::assertSame($toSchema, $this->versionExecutionResult->getToSchema());
    }

    public function testToSchemaThrowsRuntimExceptionWhenToSchemaIsNull(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Cannot call getToSchema() when toSchema is null.');

        $this->versionExecutionResult->getToSchema();
    }

    protected function setUp(): void
    {
        $this->versionExecutionResult = new ExecutionResult(
            new Version('foo'),
            Direction::UP
        );
        $this->versionExecutionResult->setSql([new Query('SELECT 1', [1], [2])]);
    }
}
