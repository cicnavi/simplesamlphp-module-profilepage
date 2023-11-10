<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Providers\Activity\DoctrineDbal;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Interfaces\ActivityInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\profilepage\Data\Trackers\Activity\DoctrineDbal\VersionedDataTracker;
use SimpleSAML\Module\profilepage\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\profilepage\Entities\Activity;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

class VersionedDataProvider implements ActivityInterface
{
    protected Store $store;

    /**
     * @throws StoreException
     */
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE,
        Store $store = null,
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
    public function getActivity(string $userIdentifier, int $maxResults = null, int $firstResult = 0): Activity\Bag
    {
        return $this->store->getActivity($userIdentifier, $maxResults, $firstResult);
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
