<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Providers\ConnectedServices\DoctrineDbal;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\Interfaces\ConnectedServicesInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Data\Trackers\ConnectedServices\DoctrineDbal\VersionedDataTracker;
use SimpleSAML\Module\accounting\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\accounting\Entities\ConnectedService;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;

class VersionedDataProvider implements ConnectedServicesInterface
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected Store $store;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE,
        Store $store = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;

        $this->store = $store ?? new Store(
            $this->moduleConfiguration,
            $this->logger,
            $this->moduleConfiguration->getClassConnectionKey(self::class),
            $connectionType
        );
    }

    /**
     * @throws StoreException
     */
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE
    ): self {
        return new self($moduleConfiguration, $logger, $connectionType);
    }

    /**
     * @throws StoreException
     */
    public function needsSetup(): bool
    {
        return $this->store->needsSetup();
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function runSetup(): void
    {
        if (! $this->needsSetup()) {
            $this->logger->warning('Run setup called, however setup is not needed.');
            return;
        }

        $this->store->runSetup();
    }

    /**
     * @throws StoreException
     */
    public function getConnectedServices(string $userIdentifier): ConnectedService\Bag
    {
        return $this->store->getConnectedServices($userIdentifier);
    }

    /**
     * @throws StoreException
     */
    public function getTracker(): ?DataTrackerInterface
    {
        return new VersionedDataTracker(
            $this->moduleConfiguration,
            $this->logger,
            ModuleConfiguration\ConnectionType::MASTER,
        );
    }
}
