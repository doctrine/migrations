<?php

namespace Doctrine\Migrations\Exception;

use Doctrine\Migrations\MigrationInfo;
use Exception;

class ExecutionFailedException extends MigrationException
{
    public function __construct(MigrationInfo $migration, Exception $previous)
    {
        parent::__construct(
            'Execution of migration "' . $migration->version . ' (' . $migration->description . ') failed.',
            0,
            $previous
        );
    }
}
