<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Metadata\MigrationInfo;
use Doctrine\Migrations\Metadata\MigrationPlanItem;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExecutionResultTest extends TestCase
{
    /** @var ExecutionResult */
    private $versionExecutionResult;

    public function testHasSql() : void
    {
        self::assertTrue($this->versionExecutionResult->hasSql());
    }

    public function testGetSetSql() : void
    {
        self::assertSame(['SELECT 1'], $this->versionExecutionResult->getSql());

        $this->versionExecutionResult->setSql(['SELECT 2']);

        self::assertSame(['SELECT 2'], $this->versionExecutionResult->getSql());
    }

    public function testGetSetParams() : void
    {
        self::assertSame([1], $this->versionExecutionResult->getParams());

        $this->versionExecutionResult->setParams([2]);

        self::assertSame([2], $this->versionExecutionResult->getParams());
    }

    public function testGetTypes() : void
    {
        self::assertSame([2], $this->versionExecutionResult->getTypes());

        $this->versionExecutionResult->setTypes([3]);

        self::assertSame([3], $this->versionExecutionResult->getTypes());
    }

    public function testGetSetTime() : void
    {
        self::assertNull($this->versionExecutionResult->getTime());

        $this->versionExecutionResult->setTime(5.5);

        self::assertSame(5.5, $this->versionExecutionResult->getTime());
    }

    public function testGetSetMemory() : void
    {
        self::assertNull($this->versionExecutionResult->getMemory());

        $this->versionExecutionResult->setMemory(555555.0);

        self::assertSame(555555.0, $this->versionExecutionResult->getMemory());
    }

    public function testSkipped() : void
    {
        self::assertFalse($this->versionExecutionResult->isSkipped());

        $this->versionExecutionResult->setSkipped(true);

        self::assertTrue($this->versionExecutionResult->isSkipped());
    }

    public function testError() : void
    {
        self::assertFalse($this->versionExecutionResult->hasError());

        $this->versionExecutionResult->setError(true);

        self::assertTrue($this->versionExecutionResult->hasError());
    }

    public function testExceptionNull() : void
    {
        self::assertNull($this->versionExecutionResult->getException());
    }

    public function testException() : void
    {
        $exception = new InvalidArgumentException();

        $this->versionExecutionResult->setException($exception);

        self::assertSame($exception, $this->versionExecutionResult->getException());
    }

    public function testToSchema() : void
    {
        $toSchema = $this->createMock(Schema::class);

        $this->versionExecutionResult->setToSchema($toSchema);

        self::assertSame($toSchema, $this->versionExecutionResult->getToSchema());
    }

    public function testToSchemaThrowsRuntimExceptionWhenToSchemaIsNull() : void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Cannot call getToSchema() when toSchema is null.');

        $this->versionExecutionResult->getToSchema();
    }

    protected function setUp() : void
    {
        $migration = $this->getMockBuilder(AbstractMigration::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $info = new MigrationInfo(new Version(get_class($migration)));
        $migrationPlanItem = new MigrationPlanItem($info, $migration, Direction::UP);
        $this->versionExecutionResult = new ExecutionResult(
            $migrationPlanItem,
            ['SELECT 1'],
            [1],
            [2]
        );
    }
}
