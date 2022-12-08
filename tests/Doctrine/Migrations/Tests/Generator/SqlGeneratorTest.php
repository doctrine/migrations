<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function sprintf;

final class SqlGeneratorTest extends TestCase
{
    private Configuration $configuration;

    /** @var AbstractPlatform&MockObject */
    private AbstractPlatform $platform;

    private SqlGenerator $migrationSqlGenerator;

    /** @var string[] */
    private array $sql;

    private TableMetadataStorageConfiguration $metadataConfig;

    public function testGenerate(): void
    {
        $configuration = new Configuration();
        $platform      = new SqlitePlatform();

        $metadataConfig = new TableMetadataStorageConfiguration();
        $configuration->setMetadataStorageConfiguration($this->metadataConfig);
        $migrationSqlGenerator = new SqlGenerator($configuration, $platform);
        $configuration->setCheckDatabasePlatform(true);

        $expectedCode = $this->prepareGeneratedCode(
            <<<'CODE'
$this->abortIf(
    !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
    "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
);

$this->addSql('SELECT 1');
$this->addSql('SELECT 2');
$this->addSql('%s');
CODE
        );

        $code = $migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertSame($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatform(): void
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

    public function testGenerationWithoutCheckingDatabasePlatformWithConfiguration(): void
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

    protected function setUp(): void
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

    private function prepareGeneratedCode(string $expectedCode): string
    {
        $this->sql = [
            'SELECT 1',
            'SELECT 2',
            'UPDATE table SET value = 1 WHERE name = 2 and value = 1 and field = 2 and active = 1',
            'SELECT * FROM migrations_table_name',
        ];

        $this->metadataConfig->setTableName('migrations_table_name');

        return sprintf(
            $expectedCode,
            (new SqlFormatter(new NullHighlighter()))->format($this->sql[2])
        );
    }
}
