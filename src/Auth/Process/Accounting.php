<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Auth\Process;

use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\profilepage\Data\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\Logger;
use SimpleSAML\Module\profilepage\Services\TrackerResolver;
use Throwable;

class Accounting extends ProcessingFilter
{
    protected ModuleConfiguration $moduleConfiguration;
    protected JobsStoreBuilder $jobsStoreBuilder;
    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;
    protected TrackerResolver $trackerResolver;

    /**
     * @param array $config
     * @param mixed $reserved
     * @param ModuleConfiguration|null $moduleConfiguration
     * @param LoggerInterface|null $logger
     * @param HelpersManager|null $helpersManager
     * @param JobsStoreBuilder|null $jobsStoreBuilder
     * @param TrackerResolver|null $trackerResolver
     */
    public function __construct(
        array &$config,
        $reserved,
        ModuleConfiguration $moduleConfiguration = null,
        LoggerInterface $logger = null,
        HelpersManager $helpersManager = null,
        JobsStoreBuilder $jobsStoreBuilder = null,
        TrackerResolver $trackerResolver = null
    ) {
        parent::__construct($config, $reserved);

        $this->moduleConfiguration = $moduleConfiguration ?? new ModuleConfiguration();
        $this->logger = $logger ?? new Logger();
        $this->helpersManager = $helpersManager ?? new HelpersManager();
        $this->jobsStoreBuilder = $jobsStoreBuilder ??
            new JobsStoreBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager);

        $this->trackerResolver = $trackerResolver ?? new TrackerResolver(
            $this->moduleConfiguration,
            $this->logger,
            $this->helpersManager
        );
    }

    /**
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection Reference is actually used by SimpleSAMLphp
     */
    public function process(array &$state): void
    {
        $this->logger->debug('Accounting started.', $state);

        try {
            if ($this->isAccountingProcessingTypeAsynchronous()) {
                // Only create authentication event job for later processing...
                $this->logger->debug('Accounting type is asynchronous, creating job for later processing.');
                $this->createAuthenticationEventJob($state);
                return;
            }

            $this->logger->debug('Accounting type is synchronous, processing now.');

            $authenticationEvent = new Event(
                $this->helpersManager->getAuthenticationEventStateResolver()->fromStateArray($state)
            );

            foreach ($this->trackerResolver->fromModuleConfiguration() as $trackerClass => $tracker) {
                    $this->logger->debug(sprintf('Processing tracker for class %s.', $trackerClass));
                    $tracker->process($authenticationEvent);
            }

            $this->logger->debug('Finished with tracker processing.');
        } catch (Throwable $exception) {
            $message = sprintf('Accounting error, skipping... Error was: %s.', $exception->getMessage());
            $this->logger->error($message, $state);
        }
    }

    protected function isAccountingProcessingTypeAsynchronous(): bool
    {
        return $this->moduleConfiguration->getAccountingProcessingType() ===
            ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS;
    }

    /**
     * @throws StoreException
     */
    protected function createAuthenticationEventJob(array $state): void
    {
        ($this->jobsStoreBuilder->build($this->moduleConfiguration->getJobsStoreClass()))
            ->enqueue(new Event\Job($state));
    }
}
