<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata\Storage;

use Psr\Log\AbstractLogger;
use Stringable;

final class DebugLogger extends AbstractLogger
{
    public int $count = 0;

    /**
     * @param mixed                   $level
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->count++;
    }
}
