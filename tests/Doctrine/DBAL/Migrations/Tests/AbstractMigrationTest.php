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
 * Class AbstractMigrationTest.
 *
 * @author Robbert van den Bogerd <rvdbogerd@ibuildings.nl>
 */
class AbstractMigrationTest extends MigrationTestCase
{
    private $config;
    private $version;
    /** @var AbstractMigrationStub */
    private $migration;
    protected $outputWriter;
    protected $output;

    protected function setUp()
    {
        $this->outputWriter = $this->getOutputWriter();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $this->version = new Version($this->config, 'Dummy', VersionDummy::class);
        $this->migration = new AbstractMigrationStub($this->version);
    }

    public function testGetDescriptionReturnsEmptyString()
    {
        $this->assertSame('', $this->migration->getDescription());
    }

    public function testWarnIfOutputMessage()
    {
        $this->migration->warnIf(true, 'Warning was thrown');
        $this->assertContains('Warning during No State: Warning was thrown', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfAddDefaultMessage()
    {
        $this->migration->warnIf(true);
        $this->assertContains('Warning during No State: Unknown Reason', $this->getOutputStreamContent($this->output));
    }

    public function testWarnIfDontOutputMessageIfFalse()
    {
        $this->migration->warnIf(false, 'trallala');
        $this->assertEquals('', $this->getOutputStreamContent($this->output));
    }

    public function testWriteInvokesOutputWriter()
    {
        $this->migration->exposed_Write('Message');
        $this->assertContains('Message', $this->getOutputStreamContent($this->output));
    }

    public function testAbortIfThrowsException()
    {
        $this->setExpectedException(AbortMigrationException::class, 'Something failed');
        $this->migration->abortIf(true, 'Something failed');
    }

    public function testAbortIfDontThrowsException()
    {
        $this->migration->abortIf(false, 'Something failed');
    }

    public function testAbortIfThrowsExceptionEvenWithoutMessage()
    {
        $this->setExpectedException(AbortMigrationException::class, 'Unknown Reason');
        $this->migration->abortIf(true);
    }

    public function testSkipIfThrowsException()
    {
        $this->setExpectedException(SkipMigrationException::class, 'Something skipped');
        $this->migration->skipIf(true, 'Something skipped');
    }

    public function testSkipIfDontThrowsException()
    {
        $this->migration->skipIf(false, 'Something skipped');
    }

    public function testThrowIrreversibleMigrationException()
    {
        $this->setExpectedException(IrreversibleMigrationException::class, 'Irreversible migration');
        $this->migration->exposed_ThrowIrreversibleMigrationException('Irreversible migration');
    }

    public function testThrowIrreversibleMigrationExceptionWithoutMessage()
    {
        $this->setExpectedException(IrreversibleMigrationException::class, 'This migration is irreversible and cannot be reverted.');
        $this->migration->exposed_ThrowIrreversibleMigrationException();
    }

    public function testIstransactional()
    {
        $this->assertTrue($this->migration->isTransactional());
    }

    public function testAddSql()
    {
        $this->migration->exposed_AddSql('tralala');
    }

    public function testHasColumn()
    {
        $this->config->getConnection()->executeQuery('CREATE TABLE IF NOT EXISTS table_with_column (test INT)');

        $this->assertTrue($this->migration->hasColumn('table_with_column', 'test'));
        $this->assertFalse($this->migration->hasColumn('table_with_column', 'invalid_column'));
        $this->assertFalse($this->migration->hasColumn('invalid_table', 'test'));
    }
}
