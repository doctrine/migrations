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
        $this->configuration->expects($this->once())
            ->method('getMigrationsNamespace')
            ->willReturn('Test');

        $this->configuration->expects($this->once())
            ->method('getMigrationsDirectory')
            ->willReturn(sys_get_temp_dir());

        $path = $this->migrationGenerator->generateMigration('1234', '// up', '// down');

        self::assertFileExists($path);

        $migrationCode = file_get_contents($path);

        self::assertContains('// up', $migrationCode);
        self::assertContains('// down', $migrationCode);

        include $path;

        self::assertTrue(class_exists('Test\Version1234'));

        unlink($path);
    }

    public function testCustomTemplate() : void
    {
        $customTemplate = sprintf('%s/template', sys_get_temp_dir());

        file_put_contents($customTemplate, 'custom template test');

        $this->configuration->expects($this->once())
            ->method('getCustomTemplate')
            ->willReturn($customTemplate);

        $path = $this->migrationGenerator->generateMigration('1234', '// up', '// down');

        self::assertFileExists($path);

        self::assertSame('custom template test', file_get_contents($path));

        unlink($path);
    }

    public function testCustomTemplateThrowsInvalidArgumentExceptionWhenTemplateMissing() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The specified template "invalid" cannot be found or is not readable.');

        $this->configuration->expects($this->once())
            ->method('getCustomTemplate')
            ->willReturn('invalid');

        $this->migrationGenerator->generateMigration('1234');
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

        $this->configuration->expects($this->once())
            ->method('getCustomTemplate')
            ->willReturn($customTemplate);

        $this->migrationGenerator->generateMigration('1234');

        unlink($path);
    }

    protected function setUp() : void
    {
        $this->configuration = $this->createMock(Configuration::class);

        $this->migrationGenerator = new Generator($this->configuration);
    }
}
