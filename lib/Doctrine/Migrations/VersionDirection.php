<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

final class VersionDirection
{
    public const UP   = 'up';
    public const DOWN = 'down';

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
