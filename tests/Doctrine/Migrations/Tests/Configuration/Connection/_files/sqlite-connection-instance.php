<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;

return DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
