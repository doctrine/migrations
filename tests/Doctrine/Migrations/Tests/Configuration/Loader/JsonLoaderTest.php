<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\JsonNotValid;
use Doctrine\Migrations\Configuration\Loader\JsonFileLoader;

class JsonLoaderTest extends AbstractLoaderTest
{
    public function load(string $prefix = '') : Configuration
    {
        $loader = new JsonFileLoader();

        return $loader->load(__DIR__ . '/../_files/config' . ($prefix!==''? ('_' . $prefix) : '') . '.json');
    }

    public function testMalformed() : void
    {
        $this->expectException(JsonNotValid::class);
        $this->load('malformed');
    }
}
