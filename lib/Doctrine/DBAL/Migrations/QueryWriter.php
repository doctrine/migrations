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
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function write(string $path, string $direction, array $queriesByVersion) : bool;
}
