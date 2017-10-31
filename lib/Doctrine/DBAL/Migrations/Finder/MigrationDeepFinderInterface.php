<?php

namespace Doctrine\DBAL\Migrations\Finder;

/**
 * A MigrationDeepFinderInterface is a MigrationFinderInterface, which locates
 * migrations not only in a directory itself, but in subdirectories of this directory,
 * too.
 */
interface MigrationDeepFinderInterface extends MigrationFinderInterface
{
}
