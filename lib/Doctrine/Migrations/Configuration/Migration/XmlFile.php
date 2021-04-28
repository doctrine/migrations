<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Migration\Exception\XmlNotValid;
use Doctrine\Migrations\Tools\BooleanStringFormatter;
use DOMDocument;
use SimpleXMLElement;

use function assert;
use function file_exists;
use function file_get_contents;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function simplexml_load_string;
use function strtr;

use const DIRECTORY_SEPARATOR;
use const LIBXML_NOCDATA;

final class XmlFile extends ConfigurationFile
{
    public function getConfiguration(): Configuration
    {
        if (! file_exists($this->file)) {
            throw FileNotFound::new($this->file);
        }

        $this->validateXml($this->file);

        $rawXML = file_get_contents($this->file);
        assert($rawXML !== false);

        $root = simplexml_load_string($rawXML, SimpleXMLElement::class, LIBXML_NOCDATA);
        assert($root !== false);

        $config = $this->extractParameters($root, true);

        if (isset($config['all_or_nothing'])) {
            $config['all_or_nothing'] = BooleanStringFormatter::toBoolean(
                $config['all_or_nothing'],
                false
            );
        }

        if (isset($config['transactional'])) {
            $config['transactional'] = BooleanStringFormatter::toBoolean(
                $config['transactional'],
                true
            );
        }

        if (isset($config['migrations_paths'])) {
            $config['migrations_paths'] = $this->getDirectoriesRelativeToFile(
                $config['migrations_paths'],
                $this->file
            );
        }

        return (new ConfigurationArray($config))->getConfiguration();
    }

    /**
     * @return mixed[]
     */
    private function extractParameters(SimpleXMLElement $root, bool $loopOverNodes): array
    {
        $config = [];

        $itemsToCheck = $loopOverNodes ? $root->children() : $root->attributes();

        if (! ($itemsToCheck instanceof SimpleXMLElement)) {
            return $config;
        }

        foreach ($itemsToCheck as $node) {
            $nodeName = strtr($node->getName(), '-', '_');
            if ($nodeName === 'migrations_paths') {
                $config['migrations_paths'] = [];
                foreach ($node->{'path'} as $pathNode) {
                    $config['migrations_paths'][(string) $pathNode['namespace']] = (string) $pathNode;
                }
            } elseif ($nodeName === 'storage' && $node->{'table-storage'} instanceof SimpleXMLElement) {
                $config['table_storage'] = $this->extractParameters($node->{'table-storage'}, false);
            } elseif ($nodeName === 'migrations') {
                $config['migrations'] = [];
                foreach ($node->{'migration'} as $pathNode) {
                    $config['migrations'][] = (string) $pathNode;
                }
            } else {
                $config[$nodeName] = (string) $node;
            }
        }

        return $config;
    }

    private function validateXml(string $file): void
    {
        try {
            libxml_use_internal_errors(true);

            $xml = new DOMDocument();

            if ($xml->load($file) === false) {
                throw XmlNotValid::malformed();
            }

            $xsdPath = __DIR__ . DIRECTORY_SEPARATOR . 'XML' . DIRECTORY_SEPARATOR . 'configuration.xsd';

            if ($xml->schemaValidate($xsdPath) === false) {
                throw XmlNotValid::failedValidation();
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(false);
        }
    }
}
