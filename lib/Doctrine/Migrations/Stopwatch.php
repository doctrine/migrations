<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * @internal
 */
class Stopwatch
{
    /** @var SymfonyStopwatch */
    private $symfonyStopwatch;

    public function __construct(SymfonyStopwatch $symfonyStopwatch)
    {
        $this->symfonyStopwatch = $symfonyStopwatch;
    }

    public function start(string $section) : StopwatchEvent
    {
        return $this->symfonyStopwatch->start($section);
    }
}
