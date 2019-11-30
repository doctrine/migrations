<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SqlFormatter;
use function sprintf;

final class SqlGeneratorTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var AbstractPlatform|MockObject */
    private $platform;

    /** @var SqlGenerator */
    private $migrationSqlGenerator;

    /** @var string[] */
    private $sql;

    /** @var TableMetadataStorageConfiguration */
    private $metadataConfig;

    public function testGenerate() : void
    {
        $this->configuration->setCheckDatabasePlatform(true);

        $expectedCode = $this->prepareGeneratedCode(
            <<<'CODE'
$this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

$this->addSql('SELECT 1');
$this->addSql('SELECT 2');
$this->addSql('%s');
CODE
        );

        $this->platform->expects(self::once())
            ->method('getName')
            ->willReturn('mysql');

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertSame($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatform() : void
    {
        $this->configuration->setCheckDatabasePlatform(true);

        $expectedCode = $this->prepareGeneratedCode(
            <<<'CODE'
$this->addSql('SELECT 1');
$this->addSql('SELECT 2');
$this->addSql('%s');
CODE
        );

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80, false);

        self::assertSame($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatformWithConfiguration() : void
    {
        $this->configuration->setCheckDatabasePlatform(false);

        $expectedCode = $this->prepareGeneratedCode(
            <<<'CODE'
$this->addSql('SELECT 1');
$this->addSql('SELECT 2');
$this->addSql('%s');
CODE
        );

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertSame($expectedCode, $code);
    }

    protected function setUp() : void
    {
        $this->configuration = new Configuration();
        $this->platform      = $this->createMock(AbstractPlatform::class);

        $this->metadataConfig = new TableMetadataStorageConfiguration();
        $this->configuration->setMetadataStorageConfiguration($this->metadataConfig);
        $this->migrationSqlGenerator = new SqlGenerator(
            $this->configuration,
            $this->platform
        );
    }

    private function prepareGeneratedCode(string $expectedCode) : string
    {
        $this->sql = [
            'SELECT 1',
            'SELECT 2',
            'UPDATE table SET value = 1 WHERE name = 2 and value = 1 and field = 2 and active = 1',
            'SELECT * FROM migrations_table_name',
        ];

        $formattedUpdate = SqlFormatter::format($this->sql[2], false);

        $expectedCode = sprintf($expectedCode, $formattedUpdate);

        $this->metadataConfig->setTableName('migrations_table_name');

        return $expectedCode;
    }
}
