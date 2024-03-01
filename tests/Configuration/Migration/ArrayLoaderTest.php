<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\Configuration\Migration\Exception\InvalidConfigurationKey;
use PHPUnit\Framework\TestCase;

class ArrayLoaderTest extends TestCase
{
    public function testInvalidKey(): void
    {
        $this->expectException(InvalidConfigurationKey::class);
        $this->expectExceptionMessage('Migrations configuration key "foo" does not exist');
        $loader = new ConfigurationArray(['foo' => 'aaa']);
        $loader->getConfiguration();
    }
}
