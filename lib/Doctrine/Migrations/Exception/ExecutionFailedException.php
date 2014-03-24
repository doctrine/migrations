<?php

namespace Doctrine\Migrations\Exception;

use Doctrine\Migrations\MigrationInfo;
use Exception;

class ExecutionFailedException extends MigrationException
{
    public function __construct(MigrationInfo $migration, Exception $previous)
    {
        parent::__construct(
            sprintf('Execution of migration "%s" (%s) failed.', $migration->getVersion(), $migration->description),
            0,
            $previous
        );
    }
}
