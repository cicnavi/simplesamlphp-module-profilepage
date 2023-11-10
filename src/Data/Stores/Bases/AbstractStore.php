<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Bases;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use SimpleSAML\Module\profilepage\Factories\SerializerFactory;
use SimpleSAML\Module\profilepage\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\Interfaces\SetupableInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

abstract class AbstractStore implements BuildableUsingModuleConfigurationInterface, SetupableInterface
{
    protected string $connectionKey;
    protected SerializerInterface $serializer;

    /**
     */
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        SerializerInterface $serializer = null,
    ) {
        $this->connectionKey = $connectionKey ??
            $moduleConfiguration->getClassConnectionKey($this->getSelfClass(), $connectionType);

        $this->serializer = $serializer ?? (new SerializerFactory($this->moduleConfiguration))->build();
    }

    /**
     * Get ReflectionClass of current store instance.
     * @return ReflectionClass
     */
    protected function getReflection(): ReflectionClass
    {
        return new ReflectionClass($this);
    }

    /**
     * Get class of the current store instance.
     * @return string
     */
    protected function getSelfClass(): string
    {
        return $this->getReflection()->getName();
    }

    /**
     * Build store instance. Must be implemented in child classes for proper return store type.
     * @param ModuleConfiguration $moduleConfiguration
     * @param LoggerInterface $logger
     * @param string|null $connectionKey
     * @return self
     */
    abstract public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self;
}
