<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use Throwable;

trait CatastrophicMigrationExceptionTrait
{
    protected Throwable $additionalException;

    public function getAdditionalException(): Throwable
    {
        return $this->additionalException;
    }

    public function setAdditionalException(Throwable $additionalException): static
    {
        $this->additionalException = $additionalException;

        return $this;
    }
}
