<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use DateTime;
use DateTimeInterface;
use Psr\Log\AbstractLogger;
use Stringable;

use function get_class;
use function gettype;
use function is_object;
use function is_scalar;
use function method_exists;
use function strpos;
use function strtr;

class TestLogger extends AbstractLogger
{
    /** @var string[] */
    public array $logs = [];

    /**
     * {@inheritdoc}
     *
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = $this->interpolate($message, $context);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string|Stringable $message
     * @param mixed[]           $context
     */
    private function interpolate($message, array $context): string
    {
        $message = (string) $message;
        if (strpos($message, '{') === false) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if ($val === null || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(DateTime::RFC3339);
            } elseif (is_object($val)) {
                $replacements["{{$key}}"] = '[object ' . get_class($val) . ']';
            } else {
                $replacements["{{$key}}"] = '[' . gettype($val) . ']';
            }
        }

        return strtr($message, $replacements);
    }
}
