<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\Services\JobRunner;
use SimpleSAML\Module\accounting\ModuleConfiguration;

function accounting_hook_cron(array &$cronInfo): void
{
    $moduleConfiguration = new ModuleConfiguration();

    $currentCronTag = $cronInfo['tag'] ?? null;

    $cronTagForJobRunner = $moduleConfiguration->getCronTagForJobRunner();

    if ($currentCronTag === $cronTagForJobRunner) {
        $state = (new JobRunner($moduleConfiguration, \SimpleSAML\Configuration::getConfig()))->run();
        $message = sprintf('Successful jobs: %s', $state->getSuccessfulJobsProcessed());
        $cronInfo['summary'][] = 'Module `oidc` clean up. Removed expired entries from storage.';
    }


    $croninfo['summary'][] = 'Module `oidc` clean up. Removed expired entries from storage.';
}