<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\PhpFile;

class PhpLoaderTest extends LoaderTest
{
    public function load(string $prefix = ''): Configuration
    {
        $loader = new PhpFile(__DIR__ . '/../_files/config' . ($prefix !== '' ? '_' . $prefix : '') . '.php');

        return $loader->getConfiguration();
    }

    public function testLoadInline(): void
    {
        $config = $this->load('instance');

        self::assertSame('foo', $config->getCustomTemplate());
    }
}
