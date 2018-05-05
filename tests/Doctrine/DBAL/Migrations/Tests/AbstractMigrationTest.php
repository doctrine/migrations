<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\IrreversibleMigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Migrations\Tests\Stub\AbstractMigrationStub;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDummy;
use Doctrine\DBAL\Migrations\Version;
use function sys_get_temp_dir;

class AbstractMigrationTest extends MigrationTestCase
{
    /** @var Configuration */
    private $config;

    /** @var Version */
    private $version;

    /** @var AbstractMigrationStub */
    private $migration;

    /** @var OutputWriter */
    protected $outputWriter;

    protected function setUp() : void
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $this->version   = new Version($this->config, 'Dummy', VersionDummy::class);
        $this->migration = new AbstractMigrationStub($this->version);
    }

    public function testGetDescriptionReturnsEmptyString() : void
    {
        self::assertSame('', $this->migration->getDescription());
    }

    public function testWarnIfOutputMessage() : void
    {
        $this->migration->warnIf(true, 'Warning was thrown');
        self::assertContains('Warning during No State: Warning was thrown', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfAddDefaultMessage() : void
    {
        $this->migration->warnIf(true);
        self::assertContains('Warning during No State: Unknown Reason', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfDontOutputMessageIfFalse() : void
    {
        $this->migration->warnIf(false, 'trallala');
        self::assertEquals('', $this->getOutputStreamContent($this->output));
    }

    public function testWriteInvokesOutputWriter() : void
    {
        $this->migration->exposedWrite('Message');
        self::assertContains('Message', $this->getOutputStreamContent($this->output));
    }

    public function testAbortIfThrowsException() : void
    {
        $this->expectException(AbortMigrationException::class);
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
        $this->expectException(AbortMigrationException::class);
        $this->expectExceptionMessage('Unknown Reason');

        $this->migration->abortIf(true);
    }

    public function testSkipIfThrowsException() : void
    {
        $this->expectException(SkipMigrationException::class);
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
        $this->expectException(IrreversibleMigrationException::class);
        $this->expectExceptionMessage('Irreversible migration');

        $this->migration->exposedThrowIrreversibleMigrationException('Irreversible migration');
    }

    public function testThrowIrreversibleMigrationExceptionWithoutMessage() : void
    {
        $this->expectException(IrreversibleMigrationException::class);
        $this->expectExceptionMessage('This migration is irreversible and cannot be reverted.');

        $this->migration->exposedThrowIrreversibleMigrationException();
    }

    public function testIstransactional() : void
    {
        self::assertTrue($this->migration->isTransactional());
    }

    public function testAddSql() : void
    {
        $this->migration->exposedAddSql('tralala');

        self::assertAttributeCount(1, 'sql', $this->migration->getVersion());
    }
}
