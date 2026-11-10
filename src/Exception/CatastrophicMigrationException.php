<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use Throwable;

interface CatastrophicMigrationException
{

    public function getAdditionalException(): Throwable;
    public function setAdditionalException(Throwable $additionalException): static;
}
