<?php

namespace Doctrine\DBAL\Migrations\Tests\Event\Listeners;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Event\MigrationsEventArgs;
use Doctrine\DBAL\Migrations\Event\Listeners\AutoCommitListener;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

class AutoCommitListenerTest extends MigrationTestCase
{
    private $conn, $listener;

    public function testListenerDoesNothingDuringADryRun()
    {
        $this->willNotCommit();

        $this->listener->onMigrationsMigrated($this->createArgs(true));
    }

    public function testListenerDoesNothingWhenConnecitonAutoCommitIsOn()
    {
        $this->willNotCommit();
        $this->conn->expects($this->once())
            ->method('isAutoCommit')
            ->willReturn(true);

        $this->listener->onMigrationsMigrated($this->createArgs(false));
    }

    public function testListenerDoesFinalCommitWhenAutoCommitIsOff()
    {
        $this->conn->expects($this->once())
            ->method('isAutoCommit')
            ->willReturn(false);
        $this->conn->expects($this->once())
            ->method('commit');

        $this->listener->onMigrationsMigrated($this->createArgs(false));
    }

    protected function setUp()
    {
        $this->conn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        try {
            $this->listener = new AutoCommitListener();
        } catch (\LogicException $e) {
            $this->markTestSkipped('DBAL 2.5 required for auto commit tests');
        }
    }

    private function willNotCommit()
    {
        $this->conn->expects(self::never())
            ->method('commit');
    }

    private function createArgs($isDryRun)
    {
        return new MigrationsEventArgs(new Configuration($this->conn), 'up', $isDryRun);
    }
}
