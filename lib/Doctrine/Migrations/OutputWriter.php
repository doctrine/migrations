<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

class OutputWriter
{
    /** @var callable */
    private $callback;

    public function __construct(?callable $callback = null)
    {
        if ($callback === null) {
            $callback = function ($message) : void {
            };
        }

        $this->callback = $callback;
    }

    public function write(string $message) : void
    {
        ($this->callback)($message);
    }
}
