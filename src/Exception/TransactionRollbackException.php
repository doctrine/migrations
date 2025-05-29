<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;
use Throwable;

class TransactionRollbackException extends RuntimeException implements CatastrophicMigrationException
{
    protected Throwable $rollbackCausationalException;

    public function getRollbackCausationalException(): Throwable
    {
        return $this->rollbackCausationalException;
    }

    public function setRollbackCausationalException(\Throwable $rollbackCausationalException): static
    {
        $this->rollbackCausationalException = $rollbackCausationalException;

        return $this;
    }
}
