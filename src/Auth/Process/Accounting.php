<?php

namespace SimpleSAML\Module\accounting\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;

class Accounting extends ProcessingFilter
{
    protected ModuleConfiguration $moduleConfiguration;
    protected JobsStoreBuilder $jobsStoreBuilder;

    /**
     * @param array $config
     * @param mixed $reserved
     * @param ModuleConfiguration|null $moduleConfiguration
     * @param JobsStoreBuilder|null $jobsStoreBuilder
     */
    public function __construct(
        array &$config,
        $reserved,
        ModuleConfiguration $moduleConfiguration = null,
        JobsStoreBuilder $jobsStoreBuilder = null
    ) {
        parent::__construct($config, $reserved);

        $this->moduleConfiguration = $moduleConfiguration ?? new ModuleConfiguration();

        $this->jobsStoreBuilder = $jobsStoreBuilder ?? new JobsStoreBuilder($this->moduleConfiguration);
    }

    public function process(array &$state): void
    {
        // TODO: Implement process() method.
    }
}
