<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\Migrations\Tests\Stub\AbstractMigrationStub;

class AbstractMigrationTest extends MigrationTestCase
{
    /** @var AbstractMigrationStub */
    private $migration;

    /** @var TestLogger */
    private $logger;

    protected function setUp() : void
    {
        $this->logger = new TestLogger();

        $this->migration = new AbstractMigrationStub($this->getSqliteConnection(), $this->logger);
    }

    public function testGetDescriptionReturnsEmptyString() : void
    {
        self::assertSame('', $this->migration->getDescription());
    }

    public function testAddSql() : void
    {
        $this->migration->exposedAddSql('SELECT 1', [1], [2]);

        self::assertSame([['SELECT 1', [1], [2]]], $this->migration->getSql());
    }

    public function testWarnIfOutputMessage() : void
    {
        $this->migration->warnIf(true, 'Warning was thrown');

        self::assertContains('Warning was thrown', $this->getLogOutput($this->logger));
    }

    public function testWarnIfAddDefaultMessage() : void
    {
        $this->migration->warnIf(true);
        self::assertContains('Unknown Reason', $this->getLogOutput($this->logger));
    }

    public function testWarnIfDontOutputMessageIfFalse() : void
    {
        $this->migration->warnIf(false, 'trallala');
        self::assertSame('', $this->getLogOutput($this->logger));
    }

    public function testWriteInvokesOutputWriter() : void
    {
        $this->migration->exposedWrite('Message');
        self::assertContains('Message', $this->getLogOutput($this->logger));
    }

    public function testAbortIfThrowsException() : void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Something failed');

        $this->migration->abortIf(true, 'Something failed');
    }

    public function testAbortIfDontThrowsException() : void
    {
        $this->migration->abortIf(false, 'Something failed');
        $this->addToAssertionCount(1);
    }

    public function testAbortIfThrowsExceptionEvenWithoutMessage() : void
    {
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Unknown Reason');

        $this->migration->abortIf(true);
    }

    public function testSkipIfThrowsException() : void
    {
        $this->expectException(SkipMigration::class);
        $this->expectExceptionMessage('Something skipped');

        $this->migration->skipIf(true, 'Something skipped');
    }

    public function testSkipIfDontThrowsException() : void
    {
        $this->migration->skipIf(false, 'Something skipped');
        $this->addToAssertionCount(1);
    }

    public function testThrowIrreversibleMigrationException() : void
    {
        $this->expectException(IrreversibleMigration::class);
        $this->expectExceptionMessage('Irreversible migration');

        $this->migration->exposedThrowIrreversibleMigrationException('Irreversible migration');
    }

    public function testThrowIrreversibleMigrationExceptionWithoutMessage() : void
    {
        $this->expectException(IrreversibleMigration::class);
        $this->expectExceptionMessage('This migration is irreversible and cannot be reverted.');

        $this->migration->exposedThrowIrreversibleMigrationException();
    }

    public function testIstransactional() : void
    {
        self::assertTrue($this->migration->isTransactional());
    }
}
