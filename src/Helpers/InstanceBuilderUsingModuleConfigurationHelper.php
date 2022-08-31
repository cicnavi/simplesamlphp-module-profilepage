<?php

namespace SimpleSAML\Module\accounting\Helpers;

use Psr\Log\LoggerInterface;
use ReflectionMethod;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

class InstanceBuilderUsingModuleConfigurationHelper
{
    /**
     * @param class-string $class
     * @param ModuleConfiguration $moduleConfiguration
     * @param LoggerInterface $logger
     * @param array $additionalArguments
     * @param string $method
     * @return BuildableUsingModuleConfigurationInterface
     * @throws Exception
     */
    public static function build(
        string $class,
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        array $additionalArguments = [],
        string $method = BuildableUsingModuleConfigurationInterface::BUILD_METHOD
    ): BuildableUsingModuleConfigurationInterface {
        try {
            self::validateClass($class);

            $allArguments = array_merge([$moduleConfiguration, $logger], $additionalArguments);

            $reflectionMethod = new ReflectionMethod($class, $method);
            /** @var BuildableUsingModuleConfigurationInterface $instance */
            $instance = $reflectionMethod->invoke(null, ...$allArguments);
        } catch (\Throwable $exception) {
            $message = \sprintf(
                'Error building instance using module configuration. Error was: %s.',
                $exception->getMessage()
            );
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        return $instance;
    }

    protected static function validateClass(string $class): void
    {
        if (!is_subclass_of($class, BuildableUsingModuleConfigurationInterface::class)) {
            $message = sprintf(
                'Class \'%s\' does not implement interface \'%s\'.',
                $class,
                BuildableUsingModuleConfigurationInterface::class
            );
            throw new UnexpectedValueException($message);
        }
    }
}
