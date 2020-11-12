<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Tests\Stub\AbstractMigrationStub;
use Doctrine\Migrations\Tests\Stub\VersionDummy;
use Doctrine\Migrations\Version\ExecutorInterface;
use Doctrine\Migrations\Version\Version;

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
    private $outputWriter;

    protected function setUp(): void
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $versionExecutor = $this->createMock(ExecutorInterface::class);

        $this->version = new Version(
            $this->config,
            'Dummy',
            VersionDummy::class,
            $versionExecutor
        );

        $this->migration = new AbstractMigrationStub($this->version);
    }

    public function testGetDescriptionReturnsEmptyString(): void
    {
        self::assertSame('', $this->migration->getDescription());
    }

    public function testWarnIfOutputMessage(): void
    {
        $this->migration->warnIf(true, 'Warning was thrown');
        self::assertStringContainsString('Warning during No State: Warning was thrown', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfAddDefaultMessage(): void
    {
        $this->migration->warnIf(true);
        self::assertStringContainsString('Warning during No State: Unknown Reason', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfDontOutputMessageIfFalse(): void
    {
        $this->migration->warnIf(false, 'trallala');
        self::assertSame('', $this->getOutputStreamContent($this->output));
    }

    public function testWriteInvokesOutputWriter(): void
    {
        $this->migration->exposedWrite('Message');
        self::assertStringContainsString('Message', $this->getOutputStreamContent($this->output));
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
