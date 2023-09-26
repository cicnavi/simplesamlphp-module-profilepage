<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\JobRunner;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Services\TrackerResolver;

function accounting_hook_cron(array &$cronInfo): void
{
    $moduleConfiguration = new ModuleConfiguration();
    $logger = new Logger();

    /** @var ?string $currentCronTag */
    $currentCronTag = $cronInfo['tag'] ?? null;

    if (!is_array($cronInfo['summary'])) {
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
        /** @psalm-suppress MixedArrayAssignment */
        $cronInfo['summary'][] = $message;
    }

    /**
     * VersionedDataTracker data retention policy handling.
     */
    $cronTagForTrackerDataRetentionPolicy = $moduleConfiguration->getCronTagForTrackerDataRetentionPolicy();
    try {
        if (
            $currentCronTag === $cronTagForTrackerDataRetentionPolicy &&
            ($retentionPolicy = $moduleConfiguration->getTrackerDataRetentionPolicy()) !== null
        ) {
            $helpersManager = new HelpersManager();
            $message = 'Handling data retention policy.';
            $logger->info($message);
            /** @psalm-suppress MixedArrayAssignment */
            $cronInfo['summary'][] = $message;
            handleDataRetentionPolicy($moduleConfiguration, $logger, $helpersManager, $retentionPolicy);
        }
    } catch (Throwable $exception) {
        $message = 'Error enforcing tracker data retention policy: ' . $exception->getMessage();
        /** @psalm-suppress MixedArrayAssignment */
        $cronInfo['summary'][] = $message;
    }
}

/**
 * @throws \SimpleSAML\Module\accounting\Exceptions\Exception
 */
function handleDataRetentionPolicy(
    ModuleConfiguration $moduleConfiguration,
    LoggerInterface $logger,
    HelpersManager $helpersManager,
    DateInterval $retentionPolicy
): void {

    $trackers = (new TrackerResolver($moduleConfiguration, $logger, $helpersManager))->fromModuleConfiguration();

    foreach ($trackers as $trackerClass => $tracker) {
        $logger->info('Applying data retention policy for class ' . $trackerClass);
        $tracker->enforceDataRetentionPolicy($retentionPolicy);
    }
}
