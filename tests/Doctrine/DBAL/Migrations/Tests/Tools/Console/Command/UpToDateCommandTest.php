<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\DBAL\Migrations\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Doctrine\DBAL\Migrations\Tools\Console\Command\UpToDateCommand
 */
class UpToDateCommandTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|OutputInterface */
    private $commandOutput;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Configuration */
    private $configuration;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigurationHelper */
    private $configurationHelper;

    /** @var UpToDateCommand */
    private $sut;

    protected function setUp() : void
    {
        parent::setUp();

        $this->configuration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationHelper = $this->getMockBuilder(ConfigurationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commandOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

        $this->sut = new UpToDateCommand();
        $this->sut->setHelperSet(new HelperSet([
            'configuration' => $this->configurationHelper,
        ]));
    }

    /**
     * @dataProvider dataIsUpToDate
     *
     * @param Version[] $migrations
     * @param string[]  $migratedVersions
     */
    public function testIsUpToDate(array $migrations, array $migratedVersions, int $exitCode) : void
    {
        // Set up mocks based on data provider.
        $this->configurationHelper->method('getMigrationConfig')->willReturn($this->configuration);
        $this->configuration->method('getMigrations')->willReturn($migrations);
        $this->configuration->method('getMigratedVersions')->willReturn($migratedVersions);

        // Command should always tell the user something.
        $this->commandOutput->expects(self::atLeastOnce())->method('writeln');

        $actual = $this->sut->execute(new ArrayInput([]), $this->commandOutput);

        // Assert.
        self::assertSame($exitCode, $actual);
    }

    /**
     * @return string[][]
     */
    public function dataIsUpToDate() : array
    {
        return [
            'up-to-date' => [
                [
                    $this->createVersion('20160614015627'),
                ],
                ['20160614015627'],
                0,
            ],
            'empty-migration-set' => [
                [],
                [],
                0,
            ],
            'one-migration-available' => [
                [
                    $this->createVersion('20150614015627'),
                ],
                [],
                1,
            ],
            'many-migrations-available' => [
                [
                    $this->createVersion('20110614015627'),
                    $this->createVersion('20120614015627'),
                    $this->createVersion('20130614015627'),
                    $this->createVersion('20140614015627'),
                ],
                ['20110614015627'],
                1,
            ],
        ];
    }

    private function createVersion(string $migration) : Version
    {
        $version = $this->getMockBuilder(Version::class)
            ->disableOriginalConstructor()
            ->getMock();

        $version->method('getVersion')->willReturn($migration);

        return $version;
    }
}
