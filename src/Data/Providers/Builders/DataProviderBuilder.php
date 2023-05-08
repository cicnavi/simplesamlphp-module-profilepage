<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Providers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\Interfaces\ActivityInterface;
use SimpleSAML\Module\accounting\Data\Providers\Interfaces\ConnectedServicesInterface;
use SimpleSAML\Module\accounting\Data\Providers\Interfaces\DataProviderInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use Throwable;

class DataProviderBuilder
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        HelpersManager $helpersManager
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
        $this->helpersManager = $helpersManager;
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
