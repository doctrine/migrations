<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\Stopwatch;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class StopwatchTest extends TestCase
{
    /** @var Stopwatch */
    private $stopwatch;

    public function testStart() : void
    {
        $stopwatchEvent = $this->stopwatch->start('test');

        self::assertInstanceOf(StopwatchEvent::class, $stopwatchEvent);
        self::assertEquals('default', $stopwatchEvent->getCategory());

        $stopwatchEvent->stop();

        self::assertNotNull($stopwatchEvent->getDuration());
    }

    protected function setUp() : void
    {
        $symfonyStopwatch = new SymfonyStopwatch();

        $this->stopwatch = new Stopwatch($symfonyStopwatch);
    }
}
