<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers\Admin;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\Utils\Auth;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class Configuration
{
    protected SspConfiguration $sspConfiguration;
    protected Session $session;
    protected LoggerInterface $logger;
    protected Utils\Auth $sspAuthUtils;

    public function __construct(
        SspConfiguration $sspConfiguration,
        Session $session,
        LoggerInterface $logger,
        Utils\Auth $sspAuthUtils = null
    ) {
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->logger = $logger;
        $this->sspAuthUtils = $sspAuthUtils ?? new Utils\Auth();

        $this->sspAuthUtils->requireAdmin();
    }

    /**
     * @param Request $request
     * @return Template
     * @throws Exception
     */
    public function status(Request $request): Template
    {
        // Instantiate ModuleConfiguration here (instead in constructor) so we can check for validation errors.
        $moduleConfiguration = null;
        $configurationValidationErrors = null;
        $jobsStore = null;
        $defaultDataTrackerAndProvider = null;
        $setupNeeded = false;

        try {
            $moduleConfiguration = new ModuleConfiguration();

            $defaultDataTrackerAndProvider =
                (new AuthenticationDataTrackerBuilder($moduleConfiguration, $this->logger))
                ->build($moduleConfiguration->getDefaultDataTrackerAndProviderClass());

            if ($defaultDataTrackerAndProvider->needsSetup()) {
                $setupNeeded = true;
            }

            if (
                $moduleConfiguration->getAccountingProcessingType() ===
                ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS
            ) {
                $jobsStore = (new JobsStoreBuilder($moduleConfiguration, $this->logger))
                    ->build($moduleConfiguration->getJobsStoreClass());
                if ($jobsStore->needsSetup()) {
                    $setupNeeded = true;
                }
            }
        } catch (Throwable $exception) {
            $configurationValidationErrors = $exception->getMessage();
        }

        $templateData = [
            'moduleConfiguration' => $moduleConfiguration,
            'configurationValidationErrors' => $configurationValidationErrors,
            'jobsStore' => $jobsStore,
            'defaultDataTrackerAndProvider' => $defaultDataTrackerAndProvider,
            'setupNeeded' => $setupNeeded
        ];

        $template = new Template($this->sspConfiguration, 'accounting:admin/configuration/status.twig');

        $template->data = $templateData;
        return $template;
    }
}
