<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Auth\Process;

use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;

class Accounting extends ProcessingFilter
{
    protected ModuleConfiguration $moduleConfiguration;
    protected JobsStoreBuilder $jobsStoreBuilder;
    protected LoggerInterface $logger;
    protected AuthenticationDataTrackerBuilder $authenticationDataTrackerBuilder;
    protected HelpersManager $helpersManager;

    /**
     * @param array $config
     * @param mixed $reserved
     * @param ModuleConfiguration|null $moduleConfiguration
     * @param LoggerInterface|null $logger
     * @param HelpersManager|null $helpersManager
     * @param JobsStoreBuilder|null $jobsStoreBuilder
     * @param AuthenticationDataTrackerBuilder|null $authenticationDataTrackerBuilder
     */
    public function __construct(
        array &$config,
        $reserved,
        ModuleConfiguration $moduleConfiguration = null,
        LoggerInterface $logger = null,
        HelpersManager $helpersManager = null,
        JobsStoreBuilder $jobsStoreBuilder = null,
        AuthenticationDataTrackerBuilder $authenticationDataTrackerBuilder = null
    ) {
        parent::__construct($config, $reserved);

        $this->moduleConfiguration = $moduleConfiguration ?? new ModuleConfiguration();
        $this->logger = $logger ?? new Logger();
        $this->helpersManager = $helpersManager ?? new HelpersManager();
        $this->jobsStoreBuilder = $jobsStoreBuilder ??
            new JobsStoreBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager);

        $this->authenticationDataTrackerBuilder = $authenticationDataTrackerBuilder ??
            new AuthenticationDataTrackerBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager);
    }

    /**
     */
    public function process(array &$state): void
    {
        try {
            $authenticationEvent = new Event(new State($state));

            if ($this->isAccountingProcessingTypeAsynchronous()) {
                // Only create authentication event job for later processing...
                $this->createAuthenticationEventJob($authenticationEvent);
                return;
            }

            // Accounting type is synchronous, so do the processing right away...
            $configuredTrackers = array_merge(
                [$this->moduleConfiguration->getDefaultDataTrackerAndProviderClass()],
                $this->moduleConfiguration->getAdditionalTrackers()
            );

            /** @var string $tracker */
            foreach ($configuredTrackers as $tracker) {
                ($this->authenticationDataTrackerBuilder->build($tracker))->process($authenticationEvent);
            }
        } catch (\Throwable $exception) {
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
    protected function createAuthenticationEventJob(Event $authenticationEvent): void
    {
        ($this->jobsStoreBuilder->build($this->moduleConfiguration->getJobsStoreClass()))
            ->enqueue(new Event\Job($authenticationEvent));
    }
}
