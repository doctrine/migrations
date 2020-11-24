<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * The RecursiveRegexFinder class recursively searches the given directory for migrations.
 */
final class RecursiveRegexFinder extends Finder
{
    /** @var string */
    private $pattern;

    public function __construct(?string $pattern = null)
    {
        $this->pattern = $pattern ?? sprintf(
            '#^.+\\%s[^\\%s]+\\.php$#i',
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * @return string[]
     */
    public function findMigrations(string $directory, ?string $namespace = null): array
    {
        $dir = $this->getRealPath($directory);

        return $this->loadMigrations(
            $this->getMatches($this->createIterator($dir)),
            $namespace
        );
    }

    private function createIterator(string $dir): RegexIterator
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
                RecursiveIteratorIterator::LEAVES_ONLY
            ),
            $this->getPattern(),
            RegexIterator::GET_MATCH
        );
    }

    private function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return string[]
     */
    private function getMatches(RegexIterator $iteratorFilesMatch): array
    {
        $files = [];
        foreach ($iteratorFilesMatch as $file) {
            $files[] = $file[0];
        }

        return $files;
    }
}
