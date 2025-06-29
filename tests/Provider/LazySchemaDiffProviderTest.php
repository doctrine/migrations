<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\Migrations\Provider\LazySchemaDiffProvider;

/**
 * Tests that pass for the SchemaDiffProvider should also pass if you wrap it
 * with the LazySchemaDiffProvider.
 */
class LazySchemaDiffProviderTest extends SchemaDiffProviderTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LazySchemaDiffProvider($this->provider);
    }
}
