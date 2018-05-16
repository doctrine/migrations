<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use function floor;
use function log;
use function pow;
use function round;

final class BytesFormatter
{
    public static function formatBytes(int $size, int $precision = 2) : string
    {
        $base     = log($size, 1024);
        $suffixes = ['', 'K', 'M', 'G', 'T'];

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }
}
