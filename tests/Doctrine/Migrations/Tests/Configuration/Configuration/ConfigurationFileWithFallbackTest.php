<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Configuration;

use Doctrine\Migrations\Configuration\Configuration\ConfigurationFileWithFallback;
use Doctrine\Migrations\Configuration\Configuration\Exception\MissingConfigurationFile;
use PHPUnit\Framework\TestCase;
use function chdir;
use function getcwd;

class ConfigurationFileWithFallbackTest extends TestCase
{
    public function testFileLoader() : void
    {
        $dir = getcwd() ?: '.';
        chdir(__DIR__ . '/../_files_loader');

        try {
            $loader        = new ConfigurationFileWithFallback(__DIR__ . '/../_files/config.php');
            $configuration = $loader->getConfiguration();

            self::assertSame('Doctrine Sandbox Migrations', $configuration->getName());
        } finally {
            chdir($dir);
        }
    }

    public function testFileLoaderFallback() : void
    {
        $dir = getcwd() ?: '.';
        chdir(__DIR__ . '/../_files_loader');

        try {
            $loader        = new ConfigurationFileWithFallback();
            $configuration = $loader->getConfiguration();

            self::assertSame('Doctrine Sandbox Migrations FilesLoader', $configuration->getName());
        } finally {
            chdir($dir);
        }
    }

    public function testMissingConfig() : void
    {
        $this->expectException(MissingConfigurationFile::class);
        $loader        = new ConfigurationFileWithFallback();
        $configuration = $loader->getConfiguration();
    }
}
