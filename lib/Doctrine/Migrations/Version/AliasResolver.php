<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

/**
 * The DefaultAliasResolver class is responsible for resolving aliases like first, current, etc. to the actual version number.
 *
 * @internal
 */
interface AliasResolver
{
    /**
     * Returns the version number from an alias.
     *
     * Supported aliases are:
     *
     * - first: The very first version before any migrations have been run.
     * - current: The current version.
     * - prev: The version prior to the current version.
     * - next: The version following the current version.
     * - latest: The latest available version.
     *
     * If an existing version number is specified, it is returned verbatim.
     */
    public function resolveVersionAlias(string $alias): Version;
}
