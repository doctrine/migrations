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
     * @param mixed[] $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->count++;
    }
}
