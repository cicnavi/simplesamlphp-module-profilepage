<?php

namespace SimpleSAML\Module\accounting\Services;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\accounting\Data\Trackers\Builders\DataTrackerBuilder;
use SimpleSAML\Module\accounting\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\ModuleConfiguration;

class TrackerResolver
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;
    protected DataProviderBuilder $dataProviderBuilder;
    protected DataTrackerBuilder $dataTrackerBuilder;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        HelpersManager $helpersManager,
        DataProviderBuilder $dataProviderBuilder = null,
        DataTrackerBuilder $dataTrackerBuilder = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
        $this->helpersManager = $helpersManager;
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
                $trackers[get_class($providersTracker)] = $providersTracker;
            }
        }

        // Process specifically configured trackers.
        foreach ($this->moduleConfiguration->getAdditionalTrackers() as $trackerClass) {
            $trackers[$trackerClass] = $this->dataTrackerBuilder->build($trackerClass);
        }

        return $trackers;
    }
}
