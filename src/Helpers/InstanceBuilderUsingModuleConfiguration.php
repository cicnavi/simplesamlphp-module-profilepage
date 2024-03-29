<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

use Psr\Log\LoggerInterface;
use ReflectionMethod;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use Throwable;

use function sprintf;

class InstanceBuilderUsingModuleConfiguration
{
    final public const BUILD_METHOD = 'build';

    /**
     * @param class-string $class
     * @throws Exception
     */
    public function build(
        string $class,
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        array $additionalArguments = [],
        string $method = self::BUILD_METHOD
    ): BuildableUsingModuleConfigurationInterface {
        try {
            $this->validateClass($class);

            $allArguments = array_merge([$moduleConfiguration, $logger], $additionalArguments);

            $reflectionMethod = new ReflectionMethod($class, $method);
            /** @var BuildableUsingModuleConfigurationInterface $instance */
            $instance = $reflectionMethod->invoke(null, ...$allArguments);
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error building instance using module configuration. Error was: %s.',
                $exception->getMessage()
            );
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        return $instance;
    }

    protected function validateClass(string $class): void
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
