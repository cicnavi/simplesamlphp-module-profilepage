<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Providers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Interfaces\ActivityInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Interfaces\ConnectedServicesInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Interfaces\DataProviderInterface;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use Throwable;

class DataProviderBuilder
{
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        protected HelpersManager $helpersManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function build(
        string $class,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE
    ): DataProviderInterface {
        try {
            // Make sure that the class implements proper interface
            if (!is_subclass_of($class, DataProviderInterface::class)) {
                $message = sprintf(
                    'Class %s does not implement interface %s.',
                    $class,
                    DataProviderInterface::class
                );
                throw new UnexpectedValueException($message);
            }

            // Build...
            /** @var DataProviderInterface $provider */
            $provider = $this->helpersManager->getInstanceBuilderUsingModuleConfiguration()->build(
                $class,
                $this->moduleConfiguration,
                $this->logger,
                [$connectionType]
            );
        } catch (Throwable $exception) {
            $message = sprintf('Error building instance for class %s. Error was: %s', $class, $exception->getMessage());
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        return $provider;
    }

    /**
     * @throws Exception
     */
    public function buildActivityProvider(
        string $class,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE
    ): ActivityInterface {
        $activityProvider =  $this->build($class, $connectionType);

        if (!is_subclass_of($activityProvider, ActivityInterface::class)) {
            $message = sprintf(
                'Class %s does not implement interface %s.',
                $class,
                ActivityInterface::class
            );
            throw new UnexpectedValueException($message);
        }

        return $activityProvider;
    }

    /**
     * @throws Exception
     */
    public function buildConnectedServicesProvider(
        string $class,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE
    ): ConnectedServicesInterface {
        $connectedServicesProvider =  $this->build($class, $connectionType);

        if (!is_subclass_of($connectedServicesProvider, ConnectedServicesInterface::class)) {
            $message = sprintf(
                'Class %s does not implement interface %s.',
                $class,
                ActivityInterface::class
            );
            throw new UnexpectedValueException($message);
        }

        return $connectedServicesProvider;
    }
}
