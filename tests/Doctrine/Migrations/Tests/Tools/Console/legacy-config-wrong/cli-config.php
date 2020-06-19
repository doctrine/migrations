<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Component\Console\Helper\HelperSet;

$conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

return new HelperSet([
    'abc' => new ConnectionHelper($conn),
]);
