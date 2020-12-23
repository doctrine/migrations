<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\OutputWriter;
use PHPUnit\Framework\TestCase;

final class OutputWriterTest extends TestCase
{
    /** @var OutputWriter */
    private $outputWriter;

    /** @var string|null */
    private $lastMessage;

    public function testDefaultCallback(): void
    {
        $outputWriter = new OutputWriter();

        $outputWriter->write('test message');

        self::assertSame('test message', $outputWriter->getLastMessage());
    }

    public function testWrite(): void
    {
        $this->outputWriter->write('test message');

        self::assertSame('test message', $this->lastMessage);
    }

    public function testSetCallback(): void
    {
        $this->outputWriter->setCallback(function (string $message): void {
            $this->lastMessage = '[LOG] ' . $message;
        });

        $this->outputWriter->write('test message');

        self::assertSame('[LOG] test message', $this->lastMessage);
    }

    protected function setUp(): void
    {
        $this->outputWriter = new OutputWriter(function (string $message): void {
            $this->lastMessage = $message;
        });
    }
}
