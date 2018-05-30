<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\SqlGenerator;
use PHPUnit\Framework\TestCase;
use SqlFormatter;
use function sprintf;

final class SqlGeneratorTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var AbstractPlatform */
    private $platform;

    /** @var SqlGenerator */
    private $migrationSqlGenerator;

    public function testGenerate() : void
    {
        $sql = [
            'SELECT 1',
            'SELECT 2',
            'UPDATE table SET value = 1 WHERE name = 2 and value = 1 and field = 2 and active = 1',
            'SELECT * FROM migrations_table_name',
        ];

        $formattedUpdate = SqlFormatter::format($sql[2], false);

        $expectedCode = <<<'CODE'
$this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

$this->addSql('SELECT 1');
$this->addSql('SELECT 2');
$this->addSql('%s');
CODE;

        $expectedCode = sprintf($expectedCode, $formattedUpdate);

        $this->configuration->expects($this->any())
            ->method('getMigrationsTableName')
            ->willReturn('migrations_table_name');

        $this->platform->expects($this->once())
            ->method('getName')
            ->willReturn('mysql');

        $code = $this->migrationSqlGenerator->generate($sql, true, 80);

        self::assertEquals($expectedCode, $code);
    }

    protected function setUp() : void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->platform      = $this->createMock(AbstractPlatform::class);

        $this->migrationSqlGenerator = new SqlGenerator(
            $this->configuration,
            $this->platform
        );
    }
}
