<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Query;

use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Tests\MigrationTestCase;

final class QueryTest extends MigrationTestCase
{
    public function testGetQuery() : void
    {
        $query = new Query('foo', ['bar', 'baz'], ['qux', 'quux']);

        self::assertSame('foo', $query->getStatement());
        self::assertSame(['bar', 'baz'], $query->getParameters());
        self::assertSame(['qux', 'quux'], $query->getTypes());
    }
}
