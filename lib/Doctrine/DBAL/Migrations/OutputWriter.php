<?php

namespace Doctrine\DBAL\Migrations;

/**
 * Simple class for outputting information from migrations.
 *
 * @link        www.doctrine-project.org
 */
class OutputWriter
{
    /** @var \Closure|null */
    private $closure;

    public function __construct(?\Closure $closure = null)
    {
        if ($closure === null) {
            $closure = function ($message) {
            };
        }
        $this->closure = $closure;
    }

    /**
     * Write output using the configured closure.
     *
     * @param string $message The message to write.
     */
    public function write($message)
    {
        $closure = $this->closure;
        $closure($message);
    }
}
