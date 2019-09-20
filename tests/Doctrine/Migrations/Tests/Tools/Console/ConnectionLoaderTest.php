<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Tools\Console\ConnectionLoader;
use Doctrine\Migrations\Tools\Console\Exception\ConnectionNotSpecified;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use function chdir;
use function getcwd;

class ConnectionLoaderTest extends TestCase
{
    /** @var ConnectionLoader */
    private $connectionLoader;

    public function testGetConnectionFromArray() : void
    {
        $input = $this->createMock(InputInterface::class);

        $input
            ->expects($this->once())
            ->method('getOption')
            ->with('db-configuration')
            ->willReturn('_files/sqlite-connection.php');

        $helperSet = $this->createMock(HelperSet::class);

        $dir = getcwd();
        chdir(__DIR__);
        try {
            self::assertInstanceOf(Connection::class, $this->connectionLoader->getConnection($input, $helperSet));
        } finally {
            chdir($dir);
        }
    }

    public function testGetConnectionFromArrayNotFound() : void
    {
        $this->expectException(ConnectionNotSpecified::class);
        $input = $this->createMock(InputInterface::class);

        $input
            ->expects($this->once())
            ->method('getOption')
            ->with('db-configuration')
            ->willReturn(__DIR__ . '/_files/wrong.php');

        $helperSet = $this->createMock(HelperSet::class);

        $dir = getcwd();
        chdir(__DIR__);
        try {
            $this->connectionLoader->getConnection($input, $helperSet);
        } finally {
            chdir($dir);
        }
    }

    public function testGetConnectionFromArrayFromFallbackFile() : void
    {
        $input = $this->createMock(InputInterface::class);

        $input
            ->expects($this->once())
            ->method('getOption')
            ->with('db-configuration')
            ->willReturn(null);

        $helperSet = $this->createMock(HelperSet::class);

        $dir = getcwd();
        chdir(__DIR__ . '/_files');
        try {
            self::assertInstanceOf(Connection::class, $this->connectionLoader->getConnection($input, $helperSet));
        } finally {
            chdir($dir);
        }
    }

    public function testGetConnectionFromHelper() : void
    {
        $input = $this->createMock(InputInterface::class);
        $input
            ->expects($this->once())
            ->method('getOption')
            ->with('db-configuration')
            ->willReturn(null);

        $connection = $this->createMock(Connection::class);

        $helper = $this->createMock(ConnectionHelper::class);
        $helper->expects($this->once())->method('getConnection')->willReturn($connection);

        $helperSet = new HelperSet();
        $helperSet->set($helper, 'connection');

        $dir = getcwd();
        chdir(__DIR__);
        try {
            self::assertSame($connection, $this->connectionLoader->getConnection($input, $helperSet));
        } finally {
            chdir($dir);
        }
    }

    protected function setUp() : void
    {
        $this->connectionLoader = new ConnectionLoader();
    }
}
