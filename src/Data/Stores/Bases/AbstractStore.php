<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Bases;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\Interfaces\SetupableInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

abstract class AbstractStore implements BuildableUsingModuleConfigurationInterface, SetupableInterface
{
    protected string $connectionKey;

    /**
     */
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ) {
        $this->connectionKey = $connectionKey ??
            $moduleConfiguration->getClassConnectionKey($this->getSelfClass(), $connectionType);
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
