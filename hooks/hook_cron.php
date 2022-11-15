<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
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

    /** @var ?string $currentCronTag */
    $currentCronTag = $cronInfo['tag'] ?? null;

    if (!isset($cronInfo['summary']) || !is_array($cronInfo['summary'])) {
        $cronInfo['summary'] = [];
    }

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

    if (!isset($cronInfo['summary']) || !is_array($cronInfo['summary'])) {
        $cronInfo['summary'] = [];
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
            $message = sprintf('Handling data retention policy.');
            $logger->info($message);
            $cronInfo['summary'][] = $message;
            handleDataRetentionPolicy($moduleConfiguration, $logger, $helpersManager, $retentionPolicy);
        }
    } catch (Throwable $exception) {
        $message = 'Error enforcing tracker data retention policy: ' . $exception->getMessage();
        $cronInfo['summary'][] = $message;
    }
}

function handleDataRetentionPolicy(
    ModuleConfiguration $moduleConfiguration,
    LoggerInterface $logger,
    HelpersManager $helpersManager,
    DateInterval $retentionPolicy
): void {
    // Handle default data tracker and provider
    (new AuthenticationDataTrackerBuilder($moduleConfiguration, $logger, $helpersManager))
        ->build($moduleConfiguration->getDefaultDataTrackerAndProviderClass())
        ->enforceDataRetentionPolicy($retentionPolicy);

    $additionalTrackers = $moduleConfiguration->getAdditionalTrackers();

    foreach ($additionalTrackers as $tracker) {
        (new AuthenticationDataTrackerBuilder($moduleConfiguration, $logger, $helpersManager))
            ->build($tracker)
            ->enforceDataRetentionPolicy($retentionPolicy);
    }
}