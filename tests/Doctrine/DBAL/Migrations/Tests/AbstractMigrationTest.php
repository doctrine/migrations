<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tests\Stub\AbstractMigrationStub;
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
    private $migration;
    private $outputWriter;

    public function setUp()
    {
        $this->outputWriter = $this->getMockBuilder('Doctrine\DBAL\Migrations\OutputWriter')
            ->getMock();

        $this->config = new Configuration($this->getSqliteConnection(), $this->outputWriter);
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');

        $this->version = new Version($this->config, 'Dummy', 'Doctrine\DBAL\Migrations\Tests\Stub\VersionDummy');
        $this->migration = new AbstractMigrationStub($this->version);
    }

    public function testGetDescriptionReturnsEmptyString()
    {
        $this->assertSame('', $this->migration->getDescription());
    }

    public function testWarnIfInvokesOutputWriter()
    {
        $this->outputWriter
            ->expects($this->once())
            ->method('write');

        $this->migration->warnIf(true, 'Warning was thrown');
    }

    public function testWriteInvokesOutputWriter()
    {
        $this->outputWriter
            ->expects($this->once())
            ->method('write')
            ->with('Message');

        $this->migration->exposed_Write('Message');
    }

    public function testAbortIfThrowsException()
    {
        $this->setExpectedException('Doctrine\DBAL\Migrations\AbortMigrationException', 'Something failed');
        $this->migration->abortIf(true, 'Something failed');
    }

    public function testSkipIfThrowsException()
    {
        $this->setExpectedException('Doctrine\DBAL\Migrations\SkipMigrationException', 'Something skipped');
        $this->migration->skipIf(true, 'Something skipped');
    }

    public function testThrowIrreversibleMigrationException()
    {
        $this->setExpectedException('Doctrine\DBAL\Migrations\IrreversibleMigrationException', 'Irreversible migration');
        $this->migration->exposed_ThrowIrreversibleMigrationException('Irreversible migration');
    }
}
