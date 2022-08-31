<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Auth\Process;

use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;

class Accounting extends ProcessingFilter
{
    protected ModuleConfiguration $moduleConfiguration;
    protected JobsStoreBuilder $jobsStoreBuilder;
    protected LoggerInterface $logger;

    /**
     * @param array $config
     * @param mixed $reserved
     * @param ModuleConfiguration|null $moduleConfiguration
     * @param LoggerInterface|null $logger
     * @param JobsStoreBuilder|null $jobsStoreBuilder
     */
    public function __construct(
        array &$config,
        $reserved,
        ModuleConfiguration $moduleConfiguration = null,
        LoggerInterface $logger = null,
        JobsStoreBuilder $jobsStoreBuilder = null
    ) {
        parent::__construct($config, $reserved);

        // TODO mivanci check if authproc works when params are not nullable.
        $this->moduleConfiguration = $moduleConfiguration ?? new ModuleConfiguration();
        $this->logger = $logger ?? new Logger();
        $this->jobsStoreBuilder = $jobsStoreBuilder ?? new JobsStoreBuilder($this->moduleConfiguration, $this->logger);
    }

    /**
     * @throws StoreException
     */
    public function process(array &$state): void
    {
        $authenticationEvent = new Event(new State($state));

        if ($this->isAccountingProcessingTypeAsynchronous()) {
            // Only create authentication event job for later processing...
            $this->createAuthenticationEventJob($authenticationEvent);
            return;
        }

        // TODO Do the processing right away...
        // Since LoggerInterface doesn't bind to SimpleSAML\Module\accounting\Services\Logger, move to implementation
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
