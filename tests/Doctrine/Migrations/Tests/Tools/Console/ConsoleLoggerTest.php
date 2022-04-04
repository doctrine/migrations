<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console;

use DateTime;
use Doctrine\Migrations\Tools\Console\ConsoleLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use stdClass;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function fopen;

use const PHP_EOL;

class ConsoleLoggerTest extends TestCase
{
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
    }

    public function testNoInfoAndDebugAsDefault(): void
    {
        $logger = new ConsoleLogger($this->output);
        $logger->info('foo');
        $logger->debug('bar');

        self::assertSame('', $this->output->fetch());
    }

    public function testLevelCanBeChanged(): void
    {
        $logger = new ConsoleLogger($this->output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);
        $logger->info('foo');
        $logger->debug('bar');

        self::assertSame('[info] foo' . PHP_EOL, $this->output->fetch());
    }

    public function testVerbosityIsREspected(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);

        $logger = new ConsoleLogger($this->output);
        $logger->info('foo');
        $logger->debug('bar');

        self::assertSame(
            '[info] foo' . PHP_EOL . '[debug] bar' . PHP_EOL,
            $this->output->fetch()
        );
    }

    public function testInterpolation(): void
    {
        $logger = new ConsoleLogger($this->output);
        $logger->error('foo {number} {date} {object} {resource} {missing}  bar', [
            'number' => 1,
            'date' => new DateTime('2010-01-01 00:08:09+00:00'),
            'object' => new stdClass(),
            'resource' => fopen('php://output', 'w'),
        ]);
        self::assertSame(
            '[error] foo 1 2010-01-01T00:08:09+00:00 [object stdClass] [resource] {missing}  bar' . PHP_EOL,
            $this->output->fetch()
        );
    }
}
