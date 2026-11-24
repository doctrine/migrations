<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\VarExporter\LazyProxyTrait;

/**
 * @internal
 *
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
class LazySchema extends Schema
{
    use LazyProxyTrait;
}
