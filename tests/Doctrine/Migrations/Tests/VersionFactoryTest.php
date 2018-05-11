<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\VersionExecutorInterface;
use Doctrine\Migrations\VersionFactory;
use PHPUnit\Framework\TestCase;

final class VersionFactoryTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var VersionExecutor */
    private $versionExecutor;

    /** @var VersionFactory */
    private $versionFactory;

    public function testCreateVersion() : void
    {
        $version = $this->versionFactory->createVersion(
            '001',
            VersionFactoryTestMigration::class
        );

        self::assertInstanceOf(Version::class, $version);
        self::assertSame($this->configuration, $version->getConfiguration());
        self::assertInstanceOf(VersionFactoryTestMigration::class, $version->getMigration());
    }

    protected function setUp() : void
    {
        $this->configuration   = $this->createMock(Configuration::class);
        $this->versionExecutor = $this->createMock(VersionExecutorInterface::class);

        $this->versionFactory = new VersionFactory(
            $this->configuration,
            $this->versionExecutor
        );
    }
}

class VersionFactoryTestMigration extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
    }

    public function down(Schema $schema) : void
    {
    }
}
