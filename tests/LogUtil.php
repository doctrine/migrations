<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use DateTimeInterface;
use Psr\Log\Test\TestLogger;
use Stringable;

use function array_map;
use function gettype;
use function implode;
use function is_object;
use function is_scalar;
use function str_contains;
use function strtr;

trait LogUtil
{
    private function getLogOutput(TestLogger $logger): string
    {
        return implode("\n", $this->getInterpolatedLogRecords($logger));
    }

    /** @return list<string> */
    private function getInterpolatedLogRecords(TestLogger $logger): array
    {
        return array_map($this->interpolate(...), $logger->records);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param array{level: mixed, message: string|Stringable, context: mixed[]} $record
     */
    private function interpolate(array $record): string
    {
        $message = (string) $record['message'];
        if (! str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($record['context'] as $key => $val) {
            if ($val === null || is_scalar($val) || $val instanceof Stringable) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(DateTimeInterface::RFC3339);
            } elseif (is_object($val)) {
                $replacements["{{$key}}"] = '[object ' . $val::class . ']';
            } else {
                $replacements["{{$key}}"] = '[' . gettype($val) . ']';
            }
        }

        return strtr($message, $replacements);
    }
}
