<?php
namespace Doctrine\DBAL\Migrations\Tests;


use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
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
     * Create migration with custom name
     */
    public function testCreateVersionWithCustomName()
    {
        $versionName = 'CustomVersionName';
        $version = new Version(new Configuration($this->getSqliteConnection()), '003',
            'Doctrine\DBAL\Migrations\Tests\VersionTest_MigrationCustom');
        $this->assertEquals($versionName, $version->getVersion());
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
 * Migration with custom name
 */
class VersionTest_MigrationCustom extends AbstractMigration
{
    public function getName()
    {
        return 'CustomVersionName';
    }
    public function down(Schema $schema) {}
    public function up(Schema $schema)   {}
}