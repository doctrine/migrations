<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\Migrations\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommandTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    /** @var DependencyFactory */
    private $dependencyFactory;

    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var MigrationStatusInfosHelper */
    private $migrationStatusInfosHelper;

    /** @var StatusCommand */
    private $statusCommand;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->migrationStatusInfosHelper->expects($this->once())
            ->method('getMigrationsInfos')
            ->willReturn(['name' => 'value']);

        $input->expects($this->once())
            ->method('getOption')
            ->with('show-versions')
            ->willReturn(true);

        $version1   = $this->createMock(Version::class);
        $migration1 = $this->createMock(AbstractMigration::class);

        $version1->expects($this->once())
            ->method('getVersion')
            ->willReturn('1');

        $version1->expects($this->once())
            ->method('getMigration')
            ->willReturn($migration1);

        $migration1->expects($this->once())
            ->method('getDescription')
            ->willReturn('Test description.');

        $version2   = $this->createMock(Version::class);
        $migration2 = $this->createMock(AbstractMigration::class);

        $version2->expects($this->once())
            ->method('getVersion')
            ->willReturn('2');

        $version2->expects($this->once())
            ->method('getMigration')
            ->willReturn($migration2);

        $migration2->expects($this->once())
            ->method('getDescription')
            ->willReturn('Test description.');

        $this->migrationRepository->expects($this->once())
            ->method('getMigrations')
            ->willReturn([$version1, $version2]);

        $this->migrationRepository->expects($this->once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1234']);

        $this->migrationRepository->expects($this->once())
            ->method('getMigratedVersions')
            ->willReturn(['1']);

        $this->configuration->expects($this->at(0))
            ->method('getDateTime')
            ->with('1234')
            ->willReturn('123456789');

        $output->expects($this->at(0))
            ->method('writeln')
            ->with("\n <info>==</info> Configuration\n");

        $output->expects($this->at(1))
            ->method('writeln')
            ->with('    <comment>>></comment> name:                                               value');

        $output->expects($this->at(2))
            ->method('writeln')
            ->with("\n <info>==</info> Available Migration Versions\n");

        $output->expects($this->at(3))
            ->method('writeln')
            ->with('    <comment>>></comment>  (<comment>1</comment>)                                                <info>migrated</info>     Test description.');

        $output->expects($this->at(4))
            ->method('writeln')
            ->with('    <comment>>></comment>  (<comment>2</comment>)                                                <error>not migrated</error>     Test description.');

        $output->expects($this->at(5))
            ->method('writeln')
            ->with("\n <info>==</info> Previously Executed Unavailable Migration Versions\n");

        $output->expects($this->at(6))
            ->method('writeln')
            ->with('    <comment>>></comment> 123456789 (<comment>1234</comment>)');

        $this->statusCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->configuration              = $this->createMock(Configuration::class);
        $this->dependencyFactory          = $this->createMock(DependencyFactory::class);
        $this->migrationRepository        = $this->createMock(MigrationRepository::class);
        $this->migrationStatusInfosHelper = $this->createMock(MigrationStatusInfosHelper::class);

        $this->dependencyFactory->expects($this->once())
            ->method('getMigrationStatusInfosHelper')
            ->willReturn($this->migrationStatusInfosHelper);

        $this->statusCommand = new StatusCommand();
        $this->statusCommand->setMigrationConfiguration($this->configuration);
        $this->statusCommand->setDependencyFactory($this->dependencyFactory);
        $this->statusCommand->setMigrationRepository($this->migrationRepository);
    }
}
