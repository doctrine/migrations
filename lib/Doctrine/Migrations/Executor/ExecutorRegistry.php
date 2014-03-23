<?php

namespace Doctrine\Migrations\Executor;

use Doctrine\Migrations\MigrationInfo;

class ExecutorRegistry
{
    /**
     * @var array<string, Executor>
     */
    private $executors = array();

    public function addExecutor($type, Executor $executor)
    {
        $this->executors[$type] = $executor;
    }

    public function findFor(MigrationInfo $migration)
    {
        if ( ! isset($this->executors[$migration->type])) {
            throw new \RuntimeException(sprintf(
                "No executor found for migration with type '%s': %s",
                $migration->type,
                $migration->script
            ));
        }

        return $this->executors[$migration->type];
    }
}
