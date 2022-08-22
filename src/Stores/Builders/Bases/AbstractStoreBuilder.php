<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Builders\Bases;

use ReflectionMethod;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Interfaces\StoreInterface;
use Throwable;

use function sprintf;
use function is_subclass_of;

class AbstractStoreBuilder
{
    protected ModuleConfiguration $moduleConfiguration;

    public function __construct(ModuleConfiguration $moduleConfiguration = null)
    {
        $this->moduleConfiguration = $moduleConfiguration ?? new ModuleConfiguration();
    }

    /**
     * @throws StoreException
     */
    protected function buildGeneric(string $class): StoreInterface
    {
        try {
            // Make sure that the class implements StoreInterface
            if (!is_subclass_of($class, StoreInterface::class)) {
                throw new StoreException(sprintf('Class %s does not implement StoreInterface.', $class));
            }

            // Build store...
            $reflectionMethod = new ReflectionMethod($class, 'build');
            /** @var StoreInterface $store */
            $store = $reflectionMethod->invoke(null, $this->moduleConfiguration);
        } catch (Throwable $exception) {
            $message = sprintf('Error building store for class %s. Error was: %s', $class, $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $store;
    }
}
