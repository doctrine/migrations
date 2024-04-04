<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use function strcmp;

final class AlphabeticalComparator implements Comparator
{
    public function compare(Version $a, Version $b): int
    {
        return strcmp($this->stripNamespace($a), $this->stripNamespace($b));
    }

    private function stripNamespace(Version $version): string
    {
        $path = explode('\\', (string) $version);
        return end($path);
    }
}
