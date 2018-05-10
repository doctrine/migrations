<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

/**
 * @internal
 */
interface ParameterFormatterInterface
{
    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    public function formatParameters(array $params, array $types) : string;
}
