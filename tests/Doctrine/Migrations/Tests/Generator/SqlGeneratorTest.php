<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\SqlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SqlGeneratorTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $configuration;

    /** @var AbstractPlatform|MockObject */
    private $platform;

    /** @var SqlGenerator */
    private $migrationSqlGenerator;

    /** @var string[] */
    private $sql;

    public function testGenerate() : void
    {
        $this->configuration->method('isDatabasePlatformChecked')->willReturn(true);

        $expectedCode = <<<'REGEXP'
/^\$this->abortIf\(\$this->connection->getDatabasePlatform\(\)->getName\(\) !== 'mysql', 'Migration can only be executed safely on \\'mysql\\'.'\);

\$this->addSql\('SELECT 1'\);
\$this->addSql\('SELECT 2'\);
\$this->addSql\('UPDATE
  table
SET
  value = 1
WHERE
  name = 2(\n )? and value = 1(\n )? and field = 2(\n )? and active = 1'\);$/
REGEXP;

        $this->platform->expects(self::once())
            ->method('getName')
            ->willReturn('mysql');

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertRegExp($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatform() : void
    {
        $this->configuration->method('isDatabasePlatformChecked')->willReturn(true);

        $expectedCode = <<<'REGEXP'
/^\$this->addSql\('SELECT 1'\);
\$this->addSql\('SELECT 2'\);
\$this->addSql\('UPDATE
  table
SET
  value = 1
WHERE
  name = 2(\n )? and value = 1(\n )? and field = 2(\n )? and active = 1'\);$/
REGEXP;

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80, false);

        self::assertRegExp($expectedCode, $code);
    }

    public function testGenerationWithoutCheckingDatabasePlatformWithConfiguration() : void
    {
        $this->configuration->method('isDatabasePlatformChecked')->willReturn(false);

        $expectedCode = <<<'REGEXP'
/^\$this->addSql\('SELECT 1'\);
\$this->addSql\('SELECT 2'\);
\$this->addSql\('UPDATE
  table
SET
  value = 1
WHERE
  name = 2(\n )? and value = 1(\n )? and field = 2(\n )? and active = 1'\);$/
REGEXP;

        $code = $this->migrationSqlGenerator->generate($this->sql, true, 80);

        self::assertRegExp($expectedCode, $code);
    }

    protected function setUp() : void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->expects(self::any())
            ->method('getMigrationsTableName')
            ->willReturn('migrations_table_name');

        $this->platform = $this->createMock(AbstractPlatform::class);

        $this->migrationSqlGenerator = new SqlGenerator(
            $this->configuration,
            $this->platform
        );

        $this->sql = [
            'SELECT 1',
            'SELECT 2',
            'UPDATE table SET value = 1 WHERE name = 2 and value = 1 and field = 2 and active = 1',
            'SELECT * FROM migrations_table_name',
        ];
    }
}
