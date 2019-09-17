<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\JsonNotValid;
use Doctrine\Migrations\Configuration\Exception\XmlNotValid;
use Doctrine\Migrations\Configuration\Loader\JsonFileLoader;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Tests\MigrationTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use const DIRECTORY_SEPARATOR;

class JsonLoaderTest extends AbstractLoaderTest
{
    public function load($prefix = ''): Configuration
    {
        $loader = new JsonFileLoader();
        return $loader->load(__DIR__ . "/../_files/config" . ($prefix? ('_'.$prefix) : '') . ".json");
    }

    public function testMalformed() : void
    {
        $this->expectException(JsonNotValid::class);
        $this->load("malformed");
    }

}
