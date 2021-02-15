<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use function array_pop;
use function explode;
use function strcmp;

class AlphabeticalWithoutNamespaceComparator implements Comparator
{
    public function compare(Version $a, Version $b): int
    {
        return strcmp($this->withoutNamespace($a), $this->withoutNamespace($b));
    }

    private function withoutNamespace(Version $version): string
    {
        $path = explode('\\', (string) $version);

        return array_pop($path);
    }
}
