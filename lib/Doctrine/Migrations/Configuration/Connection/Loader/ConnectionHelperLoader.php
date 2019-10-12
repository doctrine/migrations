<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoaderInterface;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * The ConnectionHelperLoader is responsible for loading a Doctrine\DBAL\Connection from a Symfony Console HelperSet.
 *
 * @internal
 */
final class ConnectionHelperLoader implements ConnectionLoaderInterface
{
    /** @var string */
    private $helperName;

    /** @var  HelperSet */
    private $helperSet;

    /** @var ConnectionLoaderInterface */
    private $fallback;

    public function __construct(string $helperName, ConnectionLoaderInterface $fallback, ?HelperSet $helperSet = null)
    {
        $this->helperSet  = $helperSet ?: new HelperSet();
        $this->helperName = $helperName;
        $this->fallback   = $fallback;
    }

    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     */
    public function getConnection() : Connection
    {
        if ($this->helperSet->has($this->helperName)) {
            $connectionHelper = $this->helperSet->get($this->helperName);

            if ($connectionHelper instanceof ConnectionHelper) {
                return $connectionHelper->getConnection();
            }
        }

        return $this->fallback->getConnection();
    }
}
