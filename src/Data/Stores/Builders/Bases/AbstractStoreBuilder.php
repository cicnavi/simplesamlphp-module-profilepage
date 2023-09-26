<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Builders\Bases;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\StoreInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use Throwable;

use function is_subclass_of;
use function sprintf;

abstract class AbstractStoreBuilder
{
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        protected HelpersManager $helpersManager
    ) {
    }

    abstract public function build(
        string $class,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ): StoreInterface;

    /**
     * @throws StoreException
     * TODO mivanci Utilize generics
     * TODO mivanci Utilize first class callables:
     * https://www.php.net/manual/en/functions.first_class_callable_syntax.php
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
            $store = $this->helpersManager->getInstanceBuilderUsingModuleConfiguration()->build(
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
