<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use DateTime;
use DateTimeInterface;
use Psr\Log\AbstractLogger;
use Stringable;

use function gettype;
use function is_object;
use function is_scalar;
use function str_contains;
use function strtr;

class TestLogger extends AbstractLogger
{
    /** @var string[] */
    public array $logs = [];

    /**
     * {@inheritDoc}
     *
     * @param string|Stringable $message
     * @param mixed[]           $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = $this->interpolate($message, $context);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param mixed[] $context
     */
    private function interpolate(string|Stringable $message, array $context): string
    {
        $message = (string) $message;
        if (! str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if ($val === null || is_scalar($val) || $val instanceof Stringable) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(DateTime::RFC3339);
            } elseif (is_object($val)) {
                $replacements["{{$key}}"] = '[object ' . $val::class . ']';
            } else {
                $replacements["{{$key}}"] = '[' . gettype($val) . ']';
            }
        }

        return strtr($message, $replacements);
    }
}
