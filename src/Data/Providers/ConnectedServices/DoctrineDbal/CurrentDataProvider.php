<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Providers\ConnectedServices\DoctrineDbal;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Interfaces\ConnectedServicesInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;
use SimpleSAML\Module\profilepage\Data\Trackers\ConnectedServices\DoctrineDbal\CurrentDataTracker;
use SimpleSAML\Module\profilepage\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\profilepage\Entities\ConnectedService;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

class CurrentDataProvider implements ConnectedServicesInterface
{
    protected Store $store;

    /**
     * @throws StoreException
     */
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE,
        Store $store = null
    ) {
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
        return new CurrentDataTracker(
            $this->moduleConfiguration,
            $this->logger,
            ModuleConfiguration\ConnectionType::MASTER,
        );
    }
}
