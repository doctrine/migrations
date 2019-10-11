<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use function class_exists;
use function file_get_contents;
use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

final class GeneratorTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var Generator */
    private $migrationGenerator;

    public function testGenerateMigration() : void
    {
        $path = $this->migrationGenerator->generateMigration('Test\\Version1234', '// up', '// down');

        self::assertFileExists($path);

        $migrationCode = file_get_contents($path);

        self::assertContains('// up', $migrationCode);

        include $path;

        self::assertTrue(class_exists('Test\Version1234'));

        unlink($path);
    }

    public function testCustomTemplate() : void
    {
        $customTemplate = sprintf('%s/template', sys_get_temp_dir());

        file_put_contents($customTemplate, 'custom template test');

        $this->configuration->setCustomTemplate($customTemplate);

        $path = $this->migrationGenerator->generateMigration('Test\\Version1234', '// up', '// down');

        self::assertFileExists($path);

        self::assertSame('custom template test', file_get_contents($path));

        unlink($path);
    }

    public function tesThrowsInvalidArgumentExceptionWhenNamespaceDirMissing() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path not defined for the namespace "Bar"');

        $this->migrationGenerator->generateMigration('Bar\\Version1234');
    }

    public function testCustomTemplateThrowsInvalidArgumentExceptionWhenTemplateMissing() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The specified template "invalid" cannot be found or is not readable.');

        $this->configuration->setCustomTemplate('invalid');

        $this->migrationGenerator->generateMigration('Test\\Version1234');
    }

    public function testCustomTemplateThrowsInvalidArgumentExceptionWhenTemplateEmpty() : void
    {
        $customTemplate = sprintf('%s/template', sys_get_temp_dir());

        file_put_contents($customTemplate, '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The specified template "%s" is empty.',
            $customTemplate
        ));

        $this->configuration->setCustomTemplate($customTemplate);

        $this->migrationGenerator->generateMigration('Test\\Version1234');

        unlink($customTemplate);
    }

    protected function setUp() : void
    {
        $this->configuration = new Configuration();
        $this->configuration->addMigrationsDirectory('Test', sys_get_temp_dir());
        $this->migrationGenerator = new Generator($this->configuration);
    }
}
