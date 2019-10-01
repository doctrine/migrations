<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\Exception\UnknownResource;
use Doctrine\Migrations\Configuration\Loader\ArrayLoader;
use PHPUnit\Framework\TestCase;

class ArrayLoaderTest extends TestCase
{
    public function testNotSupportedData() : void
    {
        $this->expectException(UnknownResource::class);
        $this->expectExceptionMessage('The provided resource can not be loaded by the loader "Doctrine\Migrations\Configuration\Loader\ArrayLoader".');
        $loader = new ArrayLoader();
        $loader->load(null);
    }
}
