<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Configuration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Configuration\JsonFile;

class JsonLoaderTest extends LoaderTest
{
    public function load(string $prefix = '') : Configuration
    {
        $loader = new JsonFile(__DIR__ . '/../_files/config' . ($prefix!==''? '_' . $prefix : '') . '.json');

        return $loader->getConfiguration();
    }

    public function testMalformed() : void
    {
        $this->expectException(Configuration\Exception\JsonNotValid::class);
        $this->load('malformed');
    }
}
