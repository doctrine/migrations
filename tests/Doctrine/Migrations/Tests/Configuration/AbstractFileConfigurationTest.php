<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use PHPUnit\Framework\TestCase;
use function basename;
use function chdir;
use function getcwd;

class AbstractFileConfigurationTest extends TestCase
{
    /** @var Connection */
    private $connection;

    /** @var AbstractFileConfiguration */
    private $fileConfiguration;

    public function testLoadChecksCurrentWorkingDirectory() : void
    {
        $cwd = getcwd();

        chdir(__DIR__);

        $file = basename(__FILE__);

        $this->fileConfiguration->load($file);

        $this->assertEquals(__DIR__ . '/' . $file, $this->fileConfiguration->getFile());

        chdir($cwd);
    }

    protected function setUp() : void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->fileConfiguration = new TestAbstractFileConfiguration($this->connection);
    }
}

class TestAbstractFileConfiguration extends AbstractFileConfiguration
{
    protected function doLoad(string $file) : void
    {
    }
}
