<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function assert;
use function file_exists;
use function realpath;
use function sprintf;

/**
 * @requires OS Linux|Darwin
 */
class BoxPharCompileTest extends TestCase
{
    public function testCompile(): void
    {
        $boxPharPath = __DIR__ . '/../../../../box.phar';

        if (! file_exists($boxPharPath)) {
            self::markTestSkipped('Download box with the ./download-box.sh shell script.');
        }

        $boxPharPath = realpath($boxPharPath);

        assert($boxPharPath !== false);

        $process = new Process([
            'php',
            $boxPharPath,
            'compile',
            '-vvv',
        ]);
        $process->run();

        $doctrinePharPath = realpath(__DIR__ . '/../../../../build/doctrine-migrations.phar');

        self::assertTrue(
            $process->isSuccessful(),
            sprintf("stdout: %s\nstderr: %s", $process->getOutput(), $process->getErrorOutput())
        );

        assert($doctrinePharPath !== false);
        self::assertTrue(file_exists($doctrinePharPath));

        $successful = true;

        $process = new Process(['php', $doctrinePharPath]);

        $process->start(static function ($type) use (&$successful): void {
            if ($type !== 'err') {
                return;
            }

            $successful = false;
        });

        $process->wait();

        self::assertTrue(
            $successful,
            sprintf("stdout: %s\nstderr: %s", $process->getOutput(), $process->getErrorOutput())
        );
        self::assertTrue($process->isSuccessful());
    }
}
