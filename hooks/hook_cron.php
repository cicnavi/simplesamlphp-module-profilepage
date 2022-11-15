<?php

declare(strict_types=1);

use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\JobRunner;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;

function accounting_hook_cron(array &$cronInfo): void
{
    $moduleConfiguration = new ModuleConfiguration();
    $logger = new Logger();

    $currentCronTag = $cronInfo['tag'] ?? null;

    /**
     * Job runner handling.
     */
    $cronTagForJobRunner = $moduleConfiguration->getCronTagForJobRunner();
    try {
        if ($currentCronTag === $cronTagForJobRunner) {
            $state = (new JobRunner($moduleConfiguration, Configuration::getConfig()))->run();
            foreach ($state->getStatusMessages() as $statusMessage) {
                $cronInfo['summary'][] = $statusMessage;
            }
            $message = sprintf(
                'Job processing finished with %s successful jobs, %s failed jobs; total: %s.',
                $state->getSuccessfulJobsProcessed(),
                $state->getFailedJobsProcessed(),
                $state->getTotalJobsProcessed()
            );
            $cronInfo['summary'][] = $message;
        }
    } catch (Throwable $exception) {
        $message = 'Job runner error: ' . $exception->getMessage();
        $cronInfo['summary'][] = $message;
    }

    /**
     * Tracker data retention policy handling.
     */
    $cronTagForTrackerDataRetentionPolicy = $moduleConfiguration->getCronTagForTrackerDataRetentionPolicy();
    try {
        if (
            $currentCronTag === $cronTagForTrackerDataRetentionPolicy &&
            ($retentionPolicy = $moduleConfiguration->getTrackerDataRetentionPolicy()) !== null
        ) {
            $helpersManager = new HelpersManager();
            handleDataRetentionPolicy($moduleConfiguration, $logger, $helpersManager, $retentionPolicy);
        }
    } catch (Throwable $exception) {
        $message = 'Error enforcing tracker data retention policy: ' . $exception->getMessage();
        $cronInfo['summary'][] = $message;
    }
}

function handleDataRetentionPolicy(
    ModuleConfiguration $moduleConfiguration,
    \Psr\Log\LoggerInterface $logger,
    HelpersManager $helpersManager,
    DateInterval $retentionPolicy
): void {
    // Handle default data tracker and provider
    (new AuthenticationDataTrackerBuilder($moduleConfiguration, $logger, $helpersManager))
        ->build($moduleConfiguration->getDefaultDataTrackerAndProviderClass())
        ->enforceDataRetentionPolicy($retentionPolicy);
    // TODO handle other configured trackers.
}