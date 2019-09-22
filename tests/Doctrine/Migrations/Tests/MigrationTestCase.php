<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use function implode;

abstract class MigrationTestCase extends TestCase
{
    public function getSqliteConnection() : Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }

    public function getLogOutput(LoggerInterface $logger) : string
    {
        return implode("\n", $logger->logs);
    }
}
