<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;

interface ConnectionLoaderInterface
{
    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     */
    public function chosen() : ?Connection;
}
