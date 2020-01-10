<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Configuration;

use Doctrine\Migrations\Configuration\Configuration\Exception\InvalidConfigurationFormat;
use Doctrine\Migrations\Configuration\Configuration\FormattedFile;
use PHPUnit\Framework\TestCase;

class FormattedFileTest extends TestCase
{
    public function testUnknownLoader() : void
    {
        $this->expectException(InvalidConfigurationFormat::class);

        $loader = new FormattedFile('migrations.abc');
        $loader->getConfiguration();
    }
}
