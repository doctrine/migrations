<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\IrreversibleMigrationException;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Migrations\Tests\Stub\AbstractMigrationStub;
use Doctrine\DBAL\Migrations\Tests\Stub\VersionDummy;
use Doctrine\DBAL\Migrations\Version;

/**
 * Class AbstractMigrationTest
 * @package Doctrine\DBAL\Migrations\Tests
 *
 * @author Robbert van den Bogerd <rvdbogerd@ibuildings.nl>
 */
class AbstractMigrationTest extends MigrationTestCase
{
    private $config;
    private $version;
    /** @var  AbstractMigrationStub */
    private $migration;
    protected $outputWriter;
    protected $output;

    protected function setUp()
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $this->version   = new Version($this->config, 'Dummy', VersionDummy::class);
        $this->migration = new AbstractMigrationStub($this->version);
    }

    public function testGetDescriptionReturnsEmptyString()
    {
        self::assertSame('', $this->migration->getDescription());
    }

    public function testWarnIfOutputMessage()
    {
        $this->migration->warnIf(true, 'Warning was thrown');
        self::assertContains('Warning during No State: Warning was thrown', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfAddDefaultMessage()
    {
        $this->migration->warnIf(true);
        self::assertContains('Warning during No State: Unknown Reason', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfDontOutputMessageIfFalse()
    {
        $this->migration->warnIf(false, 'trallala');
        self::assertEquals('', $this->getOutputStreamContent($this->output));
    }

    public function testWriteInvokesOutputWriter()
    {
        $this->migration->exposedWrite('Message');
        self::assertContains('Message', $this->getOutputStreamContent($this->output));
    }

    public function testAbortIfThrowsException()
    {
        $this->expectException(AbortMigrationException::class);
        $this->expectExceptionMessage('Something failed');

        $this->migration->abortIf(true, 'Something failed');
    }

    public function testAbortIfDontThrowsException()
    {
        $this->migration->abortIf(false, 'Something failed');
        $this->addToAssertionCount(1);
    }

    public function testAbortIfThrowsExceptionEvenWithoutMessage()
    {
        $this->expectException(AbortMigrationException::class);
        $this->expectExceptionMessage('Unknown Reason');

        $this->migration->abortIf(true);
    }

    public function testSkipIfThrowsException()
    {
        $this->expectException(SkipMigrationException::class);
        $this->expectExceptionMessage('Something skipped');

        $this->migration->skipIf(true, 'Something skipped');
    }

    public function testSkipIfDontThrowsException()
    {
        $this->migration->skipIf(false, 'Something skipped');
        $this->addToAssertionCount(1);
    }

    public function testThrowIrreversibleMigrationException()
    {
        $this->expectException(IrreversibleMigrationException::class);
        $this->expectExceptionMessage('Irreversible migration');

        $this->migration->exposedThrowIrreversibleMigrationException('Irreversible migration');
    }

    public function testThrowIrreversibleMigrationExceptionWithoutMessage()
    {
        $this->expectException(IrreversibleMigrationException::class);
        $this->expectExceptionMessage('This migration is irreversible and cannot be reverted.');

        $this->migration->exposedThrowIrreversibleMigrationException();
    }

    public function testIstransactional()
    {
        self::assertTrue($this->migration->isTransactional());
    }

    public function testAddSql()
    {
        $this->migration->exposedAddSql('tralala');

        self::assertAttributeCount(1, 'sql', $this->migration->getVersion());
    }
}
