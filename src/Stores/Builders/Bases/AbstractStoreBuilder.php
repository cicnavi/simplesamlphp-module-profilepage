<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Builders\Bases;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Interfaces\StoreInterface;
use Throwable;

use function sprintf;
use function is_subclass_of;

abstract class AbstractStoreBuilder
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;

    public function __construct(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger)
    {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
    }

    abstract public function build(string $class, string $connectionKey = null): StoreInterface;

    /**
     * @throws StoreException
     */
    protected function buildGeneric(string $class, array $additionalArguments = []): StoreInterface
    {
        try {
            // Make sure that the class implements StoreInterface
            if (!is_subclass_of($class, StoreInterface::class)) {
                throw new StoreException(sprintf('Class %s does not implement StoreInterface.', $class));
            }

            // Build store...
            /** @var StoreInterface $store */
            $store = InstanceBuilderUsingModuleConfigurationHelper::build(
                $class,
                $this->moduleConfiguration,
                $this->logger,
                $additionalArguments
            );
        } catch (Throwable $exception) {
            $message = sprintf('Error building store for class %s. Error was: %s', $class, $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $store;
    }
}
