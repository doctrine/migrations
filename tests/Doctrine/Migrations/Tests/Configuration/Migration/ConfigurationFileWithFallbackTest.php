<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Migration\ConfigurationFileWithFallback;
use Doctrine\Migrations\Configuration\Migration\Exception\MissingConfigurationFile;
use PHPUnit\Framework\TestCase;

use function chdir;
use function getcwd;

class ConfigurationFileWithFallbackTest extends TestCase
{
    public function testFileLoader(): void
    {
        $dir = getcwd();
        if ($dir === false) {
            $dir = '.';
        }

        chdir(__DIR__ . '/../_files_loader');

        try {
            $loader        = new ConfigurationFileWithFallback(__DIR__ . '/../_files/config.php');
            $configuration = $loader->getConfiguration();

            self::assertNull($configuration->getCustomTemplate());
        } finally {
            chdir($dir);
        }
    }

    public function testFileLoaderFallback(): void
    {
        $dir = getcwd();
        if ($dir === false) {
            $dir = '.';
        }

        chdir(__DIR__ . '/../_files_loader');

        try {
            $loader        = new ConfigurationFileWithFallback();
            $configuration = $loader->getConfiguration();

            self::assertSame('foo', $configuration->getCustomTemplate());
        } finally {
            chdir($dir);
        }
    }

    public function testMissingConfig(): void
    {
        $this->expectException(MissingConfigurationFile::class);
        $loader        = new ConfigurationFileWithFallback();
        $configuration = $loader->getConfiguration();
    }
}
