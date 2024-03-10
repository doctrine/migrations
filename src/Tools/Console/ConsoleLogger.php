<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use DateTime;
use DateTimeInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Stringable;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function gettype;
use function is_object;
use function is_scalar;
use function sprintf;
use function str_contains;
use function strtr;

/**
 * PSR-3 compliant console logger.
 *
 * @internal
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
final class ConsoleLogger extends AbstractLogger
{
    public const INFO  = 'info';
    public const ERROR = 'error';

    /** @var array<string, int> */
    private array $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::INFO => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::DEBUG => OutputInterface::VERBOSITY_VERY_VERBOSE,
    ];
    /** @var array<string, string> */
    private array $formatLevelMap = [
        LogLevel::EMERGENCY => self::ERROR,
        LogLevel::ALERT => self::ERROR,
        LogLevel::CRITICAL => self::ERROR,
        LogLevel::ERROR => self::ERROR,
        LogLevel::WARNING => self::INFO,
        LogLevel::NOTICE => self::INFO,
        LogLevel::INFO => self::INFO,
        LogLevel::DEBUG => self::INFO,
    ];

    /**
     * @param array<string, int>    $verbosityLevelMap
     * @param array<string, string> $formatLevelMap
     */
    public function __construct(
        private readonly OutputInterface $output,
        array $verbosityLevelMap = [],
        array $formatLevelMap = [],
    ) {
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
        $this->formatLevelMap    = $formatLevelMap + $this->formatLevelMap;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (! isset($this->verbosityLevelMap[$level])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        $output = $this->output;

        // Write to the error output if necessary and available
        if ($this->formatLevelMap[$level] === self::ERROR) {
            if ($this->output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput();
            }
        }

        // the if condition check isn't necessary -- it's the same one that $output will do internally anyway.
        // We only do it for efficiency here as the message formatting is relatively expensive.
        if ($output->getVerbosity() < $this->verbosityLevelMap[$level]) {
            return;
        }

        $output->writeln(sprintf('<%1$s>[%2$s] %3$s</%1$s>', $this->formatLevelMap[$level], $level, $this->interpolate($message, $context)), $this->verbosityLevelMap[$level]);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param mixed[] $context
     */
    private function interpolate(string|Stringable $message, array $context): string
    {
        $message = (string) $message;
        if (! str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if ($val === null || is_scalar($val) || $val instanceof Stringable) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(DateTime::RFC3339);
            } elseif (is_object($val)) {
                $replacements["{{$key}}"] = '[object ' . $val::class . ']';
            } else {
                $replacements["{{$key}}"] = '[' . gettype($val) . ']';
            }

            if (! isset($replacements["{{$key}}"])) {
                continue;
            }

            $replacements["{{$key}}"] = '<comment>' . $replacements["{{$key}}"] . '</comment>';
        }

        return strtr($message, $replacements);
    }
}
