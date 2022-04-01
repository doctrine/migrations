<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Tests\Stub\AbstractMigrationStub;
use Doctrine\Migrations\Tests\Stub\AbstractMigrationWithoutDownStub;

class AbstractMigrationTest extends MigrationTestCase
{
    private AbstractMigrationStub $migration;

    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();

        $this->migration = new AbstractMigrationStub($this->getSqliteConnection(), $this->logger);
    }

    public function testDownMigrationIsOptional(): void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('No down() migration implemented for "Doctrine\Migrations\Tests\Stub\AbstractMigrationWithoutDownStub"');

        $migration = new AbstractMigrationWithoutDownStub($this->getSqliteConnection(), $this->logger);
        $schema    = $this->createStub(Schema::class);
        $migration->down($schema);
    }

    public function testGetDescriptionReturnsEmptyString(): void
    {
        self::assertSame('', $this->migration->getDescription());
    }

    public function testAddSql(): void
    {
        $this->migration->exposedAddSql('SELECT 1', [1], [2]);

        self::assertEquals([new Query('SELECT 1', [1], [2])], $this->migration->getSql());
    }

    public function testWarnIfOutputMessage(): void
    {
        $this->migration->warnIf(true, 'Warning was thrown');

        self::assertStringContainsString('Warning was thrown', $this->getLogOutput($this->logger));
    }

    public function testWarnIfAddDefaultMessage(): void
    {
        $this->migration->warnIf(true);
        self::assertStringContainsString('Unknown Reason', $this->getLogOutput($this->logger));
    }

    public function testWarnIfDontOutputMessageIfFalse(): void
    {
        $this->migration->warnIf(false, 'trallala');
        self::assertSame('', $this->getLogOutput($this->logger));
    }

    public function testWriteInvokesOutputWriter(): void
    {
        $this->migration->exposedWrite('Message');
        self::assertStringContainsString('Message', $this->getLogOutput($this->logger));
    }

    public function testAbortIfThrowsException(): void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Something failed');

        $this->migration->abortIf(true, 'Something failed');
    }

    public function testAbortIfDontThrowsException(): void
    {
        $this->migration->abortIf(false, 'Something failed');
        $this->addToAssertionCount(1);
    }

    public function testAbortIfThrowsExceptionEvenWithoutMessage(): void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Unknown Reason');

        $this->migration->abortIf(true);
    }

    public function testSkipIfThrowsException(): void
    {
        $this->expectException(SkipMigration::class);
        $this->expectExceptionMessage('Something skipped');

        $this->migration->skipIf(true, 'Something skipped');
    }

    public function testSkipIfDontThrowsException(): void
    {
        $this->migration->skipIf(false, 'Something skipped');
        $this->addToAssertionCount(1);
    }

    public function testThrowIrreversibleMigrationException(): void
    {
        $this->expectException(IrreversibleMigration::class);
        $this->expectExceptionMessage('Irreversible migration');

        $this->migration->exposedThrowIrreversibleMigrationException('Irreversible migration');
    }

    public function testThrowIrreversibleMigrationExceptionWithoutMessage(): void
    {
        $this->expectException(IrreversibleMigration::class);
        $this->expectExceptionMessage('This migration is irreversible and cannot be reverted.');

        $this->migration->exposedThrowIrreversibleMigrationException();
    }

    public function testIstransactional(): void
    {
        self::assertTrue($this->migration->isTransactional());
    }
}
