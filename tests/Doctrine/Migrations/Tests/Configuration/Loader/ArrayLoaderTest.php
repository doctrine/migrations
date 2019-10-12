<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\Exception\UnableToLoadResource;
use Doctrine\Migrations\Configuration\Loader\ArrayLoader;
use PHPUnit\Framework\TestCase;

class ArrayLoaderTest extends TestCase
{
    public function testNotSupportedData() : void
    {
        $this->expectException(UnableToLoadResource::class);
        $this->expectExceptionMessage('The provided resource can not be loaded by the loader "Doctrine\Migrations\Configuration\Loader\ArrayLoader".');
        $loader = new ArrayLoader();
        $loader->load(null);
    }
}
