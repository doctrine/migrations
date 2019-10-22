<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use DateTimeImmutable;
use DateTimeZone;

/*final */class ClassNameGenerator
{
    public const VERSION_FORMAT = 'YmdHis';

    public function generateClassName(string $namespace) : string
    {
        return $namespace . '\\Version' . $this->generateVersionNumber();
    }

    private function generateVersionNumber() : string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $now->format(self::VERSION_FORMAT);
    }
}
