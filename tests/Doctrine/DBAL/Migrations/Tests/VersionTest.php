<?php
namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

/**
 * Version Test
 */
class VersionTest extends MigrationTestCase
{
    /**
     * Create simple migration
     */
    public function testCreateVersion()
    {
        $version = new Version(new Configuration($this->getSqliteConnection()), $versionName = '003',
            'Doctrine\DBAL\Migrations\Tests\VersionTest_Migration');
        $this->assertEquals($versionName, $version->getVersion());
    }

    /**
     * Create migration with description
     */
    public function testCreateVersionWithCustomName()
    {
        $versionName = '003';
        $versionDescription = 'My super migration';
        $version = new Version(new Configuration($this->getSqliteConnection()), $versionName,
            'Doctrine\DBAL\Migrations\Tests\VersionTest_MigrationDescription');
        $this->assertEquals($versionName, $version->getVersion());
        $this->assertEquals($versionDescription, $version->getMigration()->getDescription());
    }
}

/**
 * Simple migration
 */
class VersionTest_Migration extends AbstractMigration
{
    public function down(Schema $schema) {}
    public function up(Schema $schema)   {}
}

/**
 * Migration with description
 */
class VersionTest_MigrationDescription extends AbstractMigration
{
    public function getDescription()
    {
        return 'My super migration';
    }

    public function down(Schema $schema) {}
    public function up(Schema $schema)   {}
}
