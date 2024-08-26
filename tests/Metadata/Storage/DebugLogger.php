<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata\Storage;

use Psr\Log\AbstractLogger;
use Stringable;

final class DebugLogger extends AbstractLogger
{
    public int $count = 0;

    /**
     * {@inheritDoc}
     *
     * @param string|Stringable $message
     * @param mixed[]           $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->count++;
    }
}
