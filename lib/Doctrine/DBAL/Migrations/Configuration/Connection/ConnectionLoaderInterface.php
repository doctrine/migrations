<?php

namespace Doctrine\DBAL\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;

interface ConnectionLoaderInterface
{
    /**
     * read the input and return a Configuration, returns `false` if the config
     * is not supported
     * @return Connection|null
     */
    public function chosen();
}
