<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Exception\XmlNotValid;
use Doctrine\Migrations\Configuration\Exception\YamlNotAvailable;
use Doctrine\Migrations\Configuration\Exception\YamlNotValid;
use Doctrine\Migrations\Tools\BooleanStringFormatter;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class XmlFileLoader extends AbstractFileLoader
{
    /**
     * @var ArrayLoader
     */
    private $arrayLoader;

    /**
     * @param \SimpleXMLElement $root
     * @return array
     */
    private function extractParameters(\SimpleXMLElement $root, bool $nodes = true): array
    {
        $config = [];
        foreach ($nodes ? $root->children() : $root->attributes() as $node) {
            $nodeName = strtr($node->getName(), "-", "_");
            if ($nodeName === "migrations_paths") {
                $config["migrations_paths"] = [];
                foreach ($node->{"path"} as $pathNode) {
                    $config["migrations_paths"][(string)$pathNode['namespace']] = (string)$pathNode;
                }
            } elseif ($nodeName === "storage" && $node->{"table-storage"} instanceof \SimpleXMLElement) {
                $config["table_storage"] = $this->extractParameters($node->{"table-storage"}, false);
            } else {
                $config[$nodeName] = (string)$node;
            }
        }
        return $config;
    }

    public function __construct(ArrayLoader $arrayLoader = null)
    {
        $this->arrayLoader = $arrayLoader ?: new ArrayLoader();
    }
    
    public function load($file) : Configuration
    {
        if (!file_exists($file)) {
            throw FileNotFound::new();
        }
        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();

        if ($xml->load($file) === false) {
            throw XmlNotValid::malformed();
        }

        $xsdPath = __DIR__ . DIRECTORY_SEPARATOR . 'XML' . DIRECTORY_SEPARATOR . 'configuration.xsd';

        // @todo restore validation
//        if (! $xml->schemaValidate($xsdPath)) {
//            libxml_clear_errors();
//
//            throw XmlNotValid::failedValidation();
//        }

        $rawXML = file_get_contents($file);
        assert($rawXML !== false);

        $root = simplexml_load_string($rawXML, \SimpleXMLElement::class, LIBXML_NOCDATA);
        assert($xml !== false);

        $config = $this->extractParameters($root);

        if (isset($config['all_or_nothing'])) {
            $config['all_or_nothing'] = BooleanStringFormatter::toBoolean(
                $config['all_or_nothing'],
                false
            );
        }
        if (isset($config['migrations_paths'])) {
            $config['migrations_paths'] = $this->getDirectoryRelativeToFile(
                $file,
                $config['migrations_paths']
            );
        }

        return $this->arrayLoader->load($config);
    }
}
