<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\Configuration\Connection\ConfiguredConnection;
use Doctrine\Migrations\Configuration\Connection\Exception\FileNotFound;
use PHPUnit\Framework\TestCase;
use function chdir;
use function getcwd;

class ConfiguredConnectionTest extends TestCase
{
    /** @var ConfiguredConnection */
    private $connectionLoader;

    public function testGetConnectionFromArray() : void
    {
        $dir = getcwd() ?: '.';
        chdir(__DIR__);
        $this->connectionLoader = new ConfiguredConnection('_files/sqlite-connection.php');
        try {
            $conn = $this->connectionLoader->getConnection();
            self::assertInstanceOf(SqlitePlatform::class, $conn->getDatabasePlatform());
        } finally {
            chdir($dir);
        }
    }

    public function testGetConnectionFromArrayNotFound() : void
    {
        $this->expectException(FileNotFound::class);

        $dir = getcwd()?: '.';
        chdir(__DIR__);
        $this->connectionLoader = new ConfiguredConnection(__DIR__ . '/_files/wrong.php');
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
        $this->connectionLoader = new ConfiguredConnection();
        try {
            $conn = $this->connectionLoader->getConnection();
            self::assertInstanceOf(SqlitePlatform::class, $conn->getDatabasePlatform());
        } finally {
            chdir($dir);
        }
    }
}
