<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

final class MissingDependency extends RuntimeException implements MigrationException
{
}
