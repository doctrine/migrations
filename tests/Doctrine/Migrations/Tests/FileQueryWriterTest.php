<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\FileQueryWriter;
use Doctrine\Migrations\Generator\FileBuilderInterface;
use Doctrine\Migrations\Version\Direction;
use Psr\Log\LoggerInterface;
use function file_get_contents;
use function sys_get_temp_dir;
use function unlink;

final class FileQueryWriterTest extends MigrationTestCase
{
    public function testWrite() : void
    {
        $path                 = sys_get_temp_dir();
        $migrationFileBuilder = $this->createMock(FileBuilderInterface::class);
        $migrationFileBuilder
            ->expects($this->atLeastOnce())
            ->method('buildMigrationFile')
            ->with(['A'], Direction::UP)
            ->willReturn('foo');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::atLeastOnce())
            ->method('info')
            ->with(self::isType('string'));

        $writer = new FileQueryWriter(
            $migrationFileBuilder,
            $logger
        );

        self::assertTrue($writer->write($path, Direction::UP, ['A']));

        $files = $this->getSqlFilesList($path);

        self::assertCount(1, $files);

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            unlink($file);

            self::assertNotEmpty($contents);
            self::assertContains('foo', $contents);
        }
    }
}
