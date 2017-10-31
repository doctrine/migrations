<?php

namespace Doctrine\DBAL\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Connection\ConnectionLoaderInterface;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Component\Console\Helper\HelperSet;

class ConnectionHelperLoader implements ConnectionLoaderInterface
{
    /**
     * @var string
     */
    private $helperName;

    /** @var  HelperSet */
    private $helperSet;


    /**
     * ConnectionHelperLoader constructor.
     * @param HelperSet $helperSet
     * @param string $helperName
     */
    public function __construct(HelperSet $helperSet = null, $helperName)
    {
        $this->helperName = $helperName;
        if ($helperSet === null) {
            $helperSet = new HelperSet();
        }
        $this->helperSet = $helperSet;
    }

    /**
     * read the input and return a Configuration, returns `false` if the config
     * is not supported
     * @return Connection|null
     */
    public function chosen()
    {
        if ($this->helperSet->has($this->helperName)) {
            $connectionHelper = $this->helperSet->get($this->helperName);
            if ($connectionHelper instanceof ConnectionHelper) {
                return $connectionHelper->getConnection();
            }
        }

        return null;
    }
}
