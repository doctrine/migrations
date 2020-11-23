<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\Exception\YamlNotValid;
use Doctrine\Migrations\Configuration\Migration\YamlFile;

class YamlLoaderTest extends LoaderTest
{
    public function load(string $prefix = ''): Configuration
    {
        $loader = new YamlFile(__DIR__ . '/../_files/config' . ($prefix !== '' ? '_' . $prefix : '') . '.yml');

        return $loader->getConfiguration();
    }

    public function testMalformed(): void
    {
        $this->expectException(YamlNotValid::class);

        $this->load('malformed');
    }
}
