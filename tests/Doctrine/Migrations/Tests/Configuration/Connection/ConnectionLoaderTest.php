<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\Configuration\Connection\ConfiguredConnectionLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\ConnectionNotSpecified;
use PHPUnit\Framework\TestCase;
use function chdir;
use function getcwd;

class ConnectionLoaderTest extends TestCase
{
    /** @var ConfiguredConnectionLoader */
    private $connectionLoader;

    public function testGetConnectionFromArray() : void
    {
        $dir = getcwd() ?: '.';
        chdir(__DIR__);
        $this->connectionLoader = new ConfiguredConnectionLoader('_files/sqlite-connection.php');
        try {
            $conn = $this->connectionLoader->getConnection();
            self::assertInstanceOf(SqlitePlatform::class, $conn->getDatabasePlatform());
        } finally {
            chdir($dir);
        }
    }

    public function testGetConnectionFromArrayNotFound() : void
    {
        $this->expectException(ConnectionNotSpecified::class);

        $dir = getcwd()?: '.';
        chdir(__DIR__);
        $this->connectionLoader = new ConfiguredConnectionLoader(__DIR__ . '/_files/wrong.php');
        try {
            $this->connectionLoader->getConnection();
        } finally {
            chdir($dir);
        }
    }

    public function testGetConnectionFromArrayFromFallbackFile() : void
    {
        $dir = getcwd()?: '.';
        chdir(__DIR__ . '/_files');
        $this->connectionLoader = new ConfiguredConnectionLoader(null);
        try {
            $conn = $this->connectionLoader->getConnection();
            self::assertInstanceOf(SqlitePlatform::class, $conn->getDatabasePlatform());
        } finally {
            chdir($dir);
        }
    }
}
