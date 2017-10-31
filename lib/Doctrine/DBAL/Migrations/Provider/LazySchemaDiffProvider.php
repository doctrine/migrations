<?php

namespace Doctrine\DBAL\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\Proxy\LazyLoadingInterface;

class LazySchemaDiffProvider implements SchemaDiffProviderInterface
{
    /** @var  LazyLoadingValueHolderFactory */
    private $proxyFactory;

    /** @var SchemaDiffProviderInterface */
    private $originalSchemaManipulator;

    public function __construct(LazyLoadingValueHolderFactory $proxyFactory, SchemaDiffProviderInterface $originalSchemaManipulator)
    {
        $this->proxyFactory              = $proxyFactory;
        $this->originalSchemaManipulator = $originalSchemaManipulator;
    }

    public static function fromDefaultProxyFacyoryConfiguration(SchemaDiffProviderInterface $originalSchemaManipulator)
    {
        $message = 'Function %s::fromDefaultProxyFacyoryConfiguration() deprecated due to typo.'
            . 'Use %s::fromDefaultProxyFactoryConfiguration() instead';

        trigger_error(
            sprintf($message, self::class),
            E_USER_DEPRECATED
        );

        return self::fromDefaultProxyFactoryConfiguration($originalSchemaManipulator);
    }

    public static function fromDefaultProxyFactoryConfiguration(SchemaDiffProviderInterface $originalSchemaManipulator)
    {
        $proxyConfig = new Configuration();
        $proxyConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $proxyFactory = new LazyLoadingValueHolderFactory($proxyConfig);

        return new LazySchemaDiffProvider($proxyFactory, $originalSchemaManipulator);
    }

    /**
     * @return Schema
     */
    public function createFromSchema()
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        return $this->proxyFactory->createProxy(
            Schema::class,
            function (& $wrappedObject, $proxy, $method, array $parameters, & $initializer) use ($originalSchemaManipulator) {
                $initializer   = null;
                $wrappedObject = $originalSchemaManipulator->createFromSchema();

                return true;
            }
        );
    }

    /**
     * @param Schema $fromSchema
     * @return Schema
     */
    public function createToSchema(Schema $fromSchema)
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        if ($fromSchema instanceof LazyLoadingInterface && ! $fromSchema->isProxyInitialized()) {
            return $this->proxyFactory->createProxy(
                Schema::class,
                function (& $wrappedObject, $proxy, $method, array $parameters, & $initializer) use ($originalSchemaManipulator, $fromSchema) {
                    $initializer   = null;
                    $wrappedObject = $originalSchemaManipulator->createToSchema($fromSchema);

                    return true;
                }
            );
        }

        return $this->originalSchemaManipulator->createToSchema($fromSchema);
    }

    /**
     * @param Schema $fromSchema
     * @param Schema $toSchema
     *
     * @return string[]
     */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema)
    {
        if ($toSchema instanceof LazyLoadingInterface
            && ! $toSchema->isProxyInitialized()) {
            return [];
        }

        return $this->originalSchemaManipulator->getSqlDiffToMigrate($fromSchema, $toSchema);
    }
}
