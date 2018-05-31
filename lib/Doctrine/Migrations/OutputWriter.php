<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

/**
 * The OutputWriter class is responsible for writing output to the command line when executing migrations.
 */
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

    public function setCallback(callable $callback) : void
    {
        $this->callback = $callback;
    }

    public function write(string $message) : void
    {
        ($this->callback)($message);
    }
}
