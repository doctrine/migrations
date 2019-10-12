<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Loader\PhpFileLoader;

class PhpLoaderTest extends AbstractLoaderTest
{
    public function load(string $prefix = '') : Configuration
    {
        $loader = new PhpFileLoader();

        return $loader->load(__DIR__ . '/../_files/config' . ($prefix!==''? ('_' . $prefix) : '') . '.php');
    }

    public function testLoadInline() : void
    {
        $config = $this->load('instance');

        self::assertSame('inline', $config->getName());
    }
}
