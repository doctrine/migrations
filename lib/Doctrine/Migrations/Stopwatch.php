<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class Stopwatch
{
    public function start(string $section) : StopwatchEvent
    {
        $stopwatch = new SymfonyStopwatch(true);

        return $stopwatch->start($section);
    }
}
