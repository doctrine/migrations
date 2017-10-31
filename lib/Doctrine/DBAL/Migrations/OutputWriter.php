<?php

namespace Doctrine\DBAL\Migrations;

/**
 * Simple class for outputting information from migrations.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class OutputWriter
{
    private $closure;

    public function __construct(\Closure $closure = null)
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
