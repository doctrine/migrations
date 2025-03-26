<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use PHPUnit\Framework\TestCase;

use function class_exists;
use function explode;
use function implode;
use function sprintf;
use function str_repeat;

// DBAL 3 compatibility
class_exists('Doctrine\DBAL\Platforms\SqlitePlatform');

final class SqlGeneratorTest extends TestCase
{
    private Configuration $configuration;
    private SqlGenerator $migrationSqlGenerator;

    /** @var string[] */
    private array $sql;

    private TableMetadataStorageConfiguration $metadataConfig;

    public function testGenerate(): void
    {
        $configuration = new Configuration();
        $platform      = new SQLitePlatform();

        $configuration->setMetadataStorageConfiguration($this->metadataConfig);
        $migrationSqlGenerator = new SqlGenerator($configuration, $platform);
        $configuration->setCheckDatabasePlatform(true);

        // SqlitePlatform on DBAL 3, SQLitePlatform on DBAL >= 4
        $expectedPlatform = $platform::class;

        $expectedCode = $this->prepareGeneratedCode(
            <<<CODE
                \$this->abortIf(
                    !\$this->connection->getDatabasePlatform() instanceof \\$expectedPlatform,
                    "Migration can only be executed safely on '\\$expectedPlatform'."
                );

                \$this->addSql(<<<'SQL'
                    SELECT 1
                SQL);
                \$this->addSql(<<<'SQL'
                    SELECT 2
                SQL);
                \$this->addSql(<<<'SQL'
                    %s
                SQL);
                CODE,
        );

        $code = $migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertSame($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatform(): void
    {
        $this->configuration->setCheckDatabasePlatform(true);

        $expectedCode = $this->prepareGeneratedCode(
            <<<'CODE'
                $this->addSql(<<<'SQL'
                    SELECT 1
                SQL);
                $this->addSql(<<<'SQL'
                    SELECT 2
                SQL);
                $this->addSql(<<<'SQL'
                    %s
                SQL);
                CODE,
        );

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80, false);

        self::assertSame($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatformWithConfiguration(): void
    {
        $this->configuration->setCheckDatabasePlatform(false);

        $expectedCode = $this->prepareGeneratedCode(
            <<<'CODE'
                $this->addSql(<<<'SQL'
                    SELECT 1
                SQL);
                $this->addSql(<<<'SQL'
                    SELECT 2
                SQL);
                $this->addSql(<<<'SQL'
                    %s
                SQL);
                CODE,
        );

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertSame($expectedCode, $code);
    }

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $platform            = $this->createMock(AbstractPlatform::class);

        $this->metadataConfig = new TableMetadataStorageConfiguration();
        $this->configuration->setMetadataStorageConfiguration($this->metadataConfig);
        $this->migrationSqlGenerator = new SqlGenerator(
            $this->configuration,
            $platform,
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
            implode(
                "\n" . str_repeat(' ', 4),
                explode(
                    "\n",
                    (new SqlFormatter(new NullHighlighter()))->format($this->sql[2]),
                ),
            ),
        );
    }
}
