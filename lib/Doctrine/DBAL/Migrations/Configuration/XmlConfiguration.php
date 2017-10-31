<?php

namespace Doctrine\DBAL\Migrations\Configuration;

use Doctrine\DBAL\Migrations\MigrationException;

/**
 * Load migration configuration information from a XML configuration file.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class XmlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        libxml_use_internal_errors(true);
        $xml = new \DOMDocument();
        $xml->load($file);
        if ( ! $xml->schemaValidate(__DIR__ . DIRECTORY_SEPARATOR . "XML" . DIRECTORY_SEPARATOR . "configuration.xsd")) {
            libxml_clear_errors();
            throw MigrationException::configurationNotValid('XML configuration did not pass the validation test.');
        }

        $xml    = simplexml_load_file($file, "SimpleXMLElement", LIBXML_NOCDATA);
        $config = [];

        if (isset($xml->name)) {
            $config['name'] = (string) $xml->name;
        }
        if (isset($xml->table['name'])) {
            $config['table_name'] = (string) $xml->table['name'];
        }
        if (isset($xml->table['column'])) {
            $config['column_name'] = (string) $xml->table['column'];
        }
        if (isset($xml->{'migrations-namespace'})) {
            $config['migrations_namespace'] = (string) $xml->{'migrations-namespace'};
        }
        if (isset($xml->{'organize-migrations'})) {
            $config['organize_migrations'] = $xml->{'organize-migrations'};
        }
        if (isset($xml->{'migrations-directory'})) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile($file, (string) $xml->{'migrations-directory'});
        }
        if (isset($xml->migrations->migration)) {
            $config['migrations'] = $xml->migrations->migration;
        }

        $this->setConfiguration($config);
    }
}
