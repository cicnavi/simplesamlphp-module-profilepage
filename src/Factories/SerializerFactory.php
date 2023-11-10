<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Factories;

use SimpleSAML\Module\profilepage\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

class SerializerFactory
{
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration
    ) {
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function build(): SerializerInterface
    {
        $class = $this->moduleConfiguration->getSerializerClass();

        if (is_a($class, SerializerInterface::class, true)) {
            return new $class();
        }

        throw new InvalidConfigurationException('Invalid serializer class defined: ' . $class);
    }
}
