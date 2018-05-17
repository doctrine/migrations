<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Stopwatch;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\StreamOutput;
use function fopen;
use function fwrite;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function realpath;
use function rewind;
use function stream_get_contents;

abstract class MigrationTestCase extends TestCase
{
    /** @var OutputWriter */
    private $outputWriter;

    /** @var  Output */
    protected $output;

    public function getSqliteConnection() : Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }

    public function getSqliteConfiguration() : Configuration
    {
        $config = new Configuration($this->getSqliteConnection());
        $config->setMigrationsDirectory(__DIR__ . '/Stub/migration-empty-folder');
        $config->setMigrationsNamespace('DoctrineMigrations');

        return $config;
    }

    public function getOutputStream() : StreamOutput
    {
        $stream = fopen('php://memory', 'r+', false);

        return new StreamOutput($stream);
    }

    public function getOutputStreamContent(StreamOutput $streamOutput) : string
    {
        $stream = $streamOutput->getStream();
        rewind($stream);

        return stream_get_contents($stream);
    }

    /** @return resource */
    public function getInputStream(string $input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }

    protected function getOutputWriter() : OutputWriter
    {
        if (! $this->outputWriter) {
            $this->output       = $this->getOutputStream();
            $output             = $this->output;
            $this->outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });
        }
        return $this->outputWriter;
    }

    /** @throws Exception */
    protected function createTempDirForMigrations(string $path) : void
    {
        if (! mkdir($path)) {
            throw new Exception('fail to create a temporary folder for the tests at ' . $path);
        }
    }

    /** @return string[] */
    protected function getSqlFilesList(string $path) : array
    {
        if (is_dir($path)) {
            return glob(realpath($path) . '/*.sql');
        }

        if (is_file($path)) {
            return [$path];
        }

        return [];
    }

    protected function createTestMigrator(Configuration $config) : Migrator
    {
        $dependencyFactory   = $config->getDependencyFactory();
        $migrationRepository = $dependencyFactory->getMigrationRepository();
        $outputWriter        = $dependencyFactory->getOutputWriter();
        $stopwatch           = new Stopwatch();

        return new Migrator($config, $migrationRepository, $outputWriter, $stopwatch);
    }

    /**
     * @return mixed[]
     */
    protected function getMigratorConstructorArgs(Configuration $config) : array
    {
        $dependencyFactory   = $config->getDependencyFactory();
        $migrationRepository = $dependencyFactory->getMigrationRepository();
        $outputWriter        = $dependencyFactory->getOutputWriter();
        $stopwatch           = new Stopwatch();

        return [$config, $migrationRepository, $outputWriter, $stopwatch];
    }
}
