<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\Exception\JsonNotValid;
use Doctrine\Migrations\Configuration\Migration\JsonFile;

class JsonLoaderTest extends LoaderTest
{
    public function load(string $prefix = ''): Configuration
    {
        $loader = new JsonFile(__DIR__ . '/../_files/config' . ($prefix !== '' ? '_' . $prefix : '') . '.json');

        return $loader->getConfiguration();
    }

    public function testMalformed(): void
    {
        $this->expectException(JsonNotValid::class);
        $this->load('malformed');
    }
}
