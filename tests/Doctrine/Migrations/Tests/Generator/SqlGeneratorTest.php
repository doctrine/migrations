<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\SqlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SqlFormatter;
use function sprintf;

final class SqlGeneratorTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $configuration;

    /** @var AbstractPlatform|MockObject */
    private $platform;

    /** @var SqlGenerator */
    private $migrationSqlGenerator;

    /** @var array */
    private $sql;

    public function testGenerate(): void
    {

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
            ->willReturn('mysql')
        ;


        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertSame($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatform(): void
    {
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

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->platform      = $this->createMock(AbstractPlatform::class);

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

        $formattedUpdate = SqlFormatter::format($this->sql[2], false);

        $expectedCode = sprintf($expectedCode, $formattedUpdate);

        $this->configuration->expects(self::any())
            ->method('getMigrationsTableName')
            ->willReturn('migrations_table_name')
        ;

        return $expectedCode;
    }
}
