<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Services;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\profilepage\Data\Trackers\Builders\DataTrackerBuilder;
use SimpleSAML\Module\profilepage\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

class TrackerResolver
{
    protected DataProviderBuilder $dataProviderBuilder;
    protected DataTrackerBuilder $dataTrackerBuilder;

    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        protected HelpersManager $helpersManager,
        DataProviderBuilder $dataProviderBuilder = null,
        DataTrackerBuilder $dataTrackerBuilder = null
    ) {
        $this->dataProviderBuilder = $dataProviderBuilder ?? new DataProviderBuilder(
            $this->moduleConfiguration,
            $this->logger,
            $this->helpersManager
        );
        $this->dataTrackerBuilder = $dataTrackerBuilder ?? new DataTrackerBuilder(
            $this->moduleConfiguration,
            $this->logger,
            $this->helpersManager
        );
    }

    /**
     * @return array<string,DataTrackerInterface>
     * @throws Exception
     */
    public function fromModuleConfiguration(): array
    {
        $trackers = [];

        foreach ($this->moduleConfiguration->getProviderClasses() as $providerClass) {
            if (
                ($provider = $this->dataProviderBuilder->build($providerClass)) &&
                ($providersTracker = $provider->getTracker()) !== null
            ) {
                $trackers[$providersTracker::class] = $providersTracker;
            }
        }

        // Process specifically configured trackers.
        foreach ($this->moduleConfiguration->getAdditionalTrackers() as $trackerClass) {
            $trackers[$trackerClass] = $this->dataTrackerBuilder->build($trackerClass);
        }

        return $trackers;
    }
}
