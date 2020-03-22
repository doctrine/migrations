<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * The LazySchemaDiffProvider is responsible for lazily generating the from schema when diffing two schemas
 * to produce a migration.
 *
 * @internal
 */
class LazySchemaDiffProvider implements SchemaDiffProvider
{
    /** @var LazyLoadingValueHolderFactory */
    private $proxyFactory;

    /** @var SchemaDiffProvider */
    private $originalSchemaManipulator;

    public function __construct(
        LazyLoadingValueHolderFactory $proxyFactory,
        SchemaDiffProvider $originalSchemaManipulator
    ) {
        $this->proxyFactory              = $proxyFactory;
        $this->originalSchemaManipulator = $originalSchemaManipulator;
    }

    public static function fromDefaultProxyFactoryConfiguration(
        SchemaDiffProvider $originalSchemaManipulator
    ) : LazySchemaDiffProvider {
        $proxyConfig = new Configuration();
        $proxyConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $proxyFactory = new LazyLoadingValueHolderFactory($proxyConfig);

        return new LazySchemaDiffProvider($proxyFactory, $originalSchemaManipulator);
    }

    public function createFromSchema() : Schema
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        return $this->proxyFactory->createProxy(
            Schema::class,
            static function (&$wrappedObject, $proxy, $method, array $parameters, &$initializer) use ($originalSchemaManipulator) : bool {
                $initializer = null;

                $wrappedObject = $originalSchemaManipulator->createFromSchema();

                return true;
            }
        );
    }

    public function createToSchema(Schema $fromSchema) : Schema
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        if ($fromSchema instanceof LazyLoadingInterface && ! $fromSchema->isProxyInitialized()) {
            return $this->proxyFactory->createProxy(
                Schema::class,
                static function (&$wrappedObject, $proxy, $method, array $parameters, &$initializer) use ($originalSchemaManipulator, $fromSchema) : bool {
                    $initializer = null;

                    $wrappedObject = $originalSchemaManipulator->createToSchema($fromSchema);

                    return true;
                }
            );
        }

        return $this->originalSchemaManipulator->createToSchema($fromSchema);
    }

    /** @return string[] */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema) : array
    {
        if ($toSchema instanceof LazyLoadingInterface
            && ! $toSchema->isProxyInitialized()) {
            return [];
        }

        return $this->originalSchemaManipulator->getSqlDiffToMigrate($fromSchema, $toSchema);
    }
}
