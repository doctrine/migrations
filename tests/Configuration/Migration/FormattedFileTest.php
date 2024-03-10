<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Migration\Exception\InvalidConfigurationFormat;
use Doctrine\Migrations\Configuration\Migration\FormattedFile;
use PHPUnit\Framework\TestCase;

class FormattedFileTest extends TestCase
{
    public function testUnknownLoader(): void
    {
        $this->expectException(InvalidConfigurationFormat::class);

        $loader = new FormattedFile('migrations.abc');
        $loader->getConfiguration();
    }
}
