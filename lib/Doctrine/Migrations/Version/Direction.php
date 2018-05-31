<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

final class Direction
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
