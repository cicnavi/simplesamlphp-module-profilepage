<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\Services\JobRunner;
use SimpleSAML\Module\accounting\ModuleConfiguration;

function accounting_hook_cron(array &$cronInfo): void
{
    $moduleConfiguration = new ModuleConfiguration();

    $currentCronTag = $cronInfo['tag'] ?? null;

    $cronTagForJobRunner = $moduleConfiguration->getCronTagForJobRunner();

    try {
        if ($currentCronTag === $cronTagForJobRunner) {
            $state = (new JobRunner($moduleConfiguration, \SimpleSAML\Configuration::getConfig()))->run();
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
}