<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tests\VersionExecutor;
use Doctrine\Migrations\Version\ExecutorInterface;
use Doctrine\Migrations\Version\Factory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

final class FactoryTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var VersionExecutor */
    private $versionExecutor;

    /** @var Factory */
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
        $this->versionExecutor = $this->createMock(ExecutorInterface::class);

        $this->versionFactory = new Factory(
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
