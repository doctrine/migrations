<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Query;

use Doctrine\Migrations\Query\Exception\InvalidArguments;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Tests\MigrationTestCase;

final class QueryTest extends MigrationTestCase
{
    public function testGetQuery(): void
    {
        $query = new Query('foo', ['bar', 'baz'], ['qux', 'quux']);

        self::assertSame('foo', $query->getStatement());
        self::assertSame('foo', (string) $query);
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame(['qux', 'quux'], $query->getTypes());
    }

    public function testInvalidTypeArguments(): void
    {
        $this->expectException(InvalidArguments::class);
        $this->expectExceptionMessage('The number of types (2) is higher than the number of passed parameters (1) for the query "Select 1".');

        new Query('Select 1', ['bar'], ['qux', 'quux']);
    }
}
