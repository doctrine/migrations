<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

/**
 * @since  1.6.0
 * @author Luís Cobucci <lcobucci@gmail.com>
 */
interface QueryWriter
{
    /**
     * @param string $path
     * @param string $direction
     * @param array $queriesByVersion
     * @return bool
     */
    public function write(string $path, string $direction, array $queriesByVersion) : bool;
}
