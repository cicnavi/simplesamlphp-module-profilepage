<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Builders\Bases;

use ReflectionMethod;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Interfaces\StoreInterface;
use Throwable;

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
    public function build(string $class): StoreInterface
    {
        // Make sure that the class implements StoreInterface
        if (!is_subclass_of($class, StoreInterface::class)) {
            throw new StoreException(\sprintf('Class %s does not implement StoreInterface.', $class));
        }

        try {
            $reflectionMethod = new ReflectionMethod($class, 'build');
            /** @var StoreInterface $store */
            $store = $reflectionMethod->invoke(null, $this->moduleConfiguration);
        } catch (Throwable $exception) {
            $message = 'Error building store for class %s.';
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $store;
    }
}
