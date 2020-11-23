<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use function strcmp;

final class AlphabeticalComparator implements Comparator
{
    public function compare(Version $a, Version $b): int
    {
        return strcmp((string) $a, (string) $b);
    }
}
