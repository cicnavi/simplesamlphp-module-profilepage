<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers\Admin;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class Configuration
{
    protected SspConfiguration $sspConfiguration;
    protected Session $session;
    protected LoggerInterface $logger;
    protected Utils\Auth $sspAuthUtils;
    protected HelpersManager $helpersManager;

    /**
     * @throws \SimpleSAML\Error\Exception
     */
    public function __construct(
        SspConfiguration $sspConfiguration,
        Session $session,
        LoggerInterface $logger,
        HelpersManager $helpersManager,
        Utils\Auth $sspAuthUtils = null
    ) {
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->logger = $logger;
        $this->helpersManager = $helpersManager;
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
        $additionalTrackers = [];
        $setupNeeded = false;
        $runSetup = $request->query->has('runSetup');

        try {
            $moduleConfiguration = new ModuleConfiguration();

            $defaultDataTrackerAndProvider =
                (new AuthenticationDataTrackerBuilder($moduleConfiguration, $this->logger, $this->helpersManager))
                ->build($moduleConfiguration->getDefaultDataTrackerAndProviderClass());

            if ($defaultDataTrackerAndProvider->needsSetup()) {
                if ($runSetup) {
                    $defaultDataTrackerAndProvider->runSetup();
                } else {
                    $setupNeeded = true;
                }
            }

            if (
                $moduleConfiguration->getAccountingProcessingType() ===
                ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS
            ) {
                $jobsStore = (new JobsStoreBuilder($moduleConfiguration, $this->logger, $this->helpersManager))
                    ->build($moduleConfiguration->getJobsStoreClass());
                if ($jobsStore->needsSetup()) {
                    if ($runSetup) {
                        $jobsStore->runSetup();
                    } else {
                        $setupNeeded = true;
                    }
                }
            }

            foreach ($moduleConfiguration->getAdditionalTrackers() as $trackerClass) {
                $additionalTrackerInstance =
                    (new AuthenticationDataTrackerBuilder($moduleConfiguration, $this->logger, $this->helpersManager))
                    ->build($trackerClass);

                if ($additionalTrackerInstance->needsSetup()) {
                    if ($runSetup) {
                        $additionalTrackerInstance->runSetup();
                    } else {
                        $setupNeeded = true;
                    }
                }
                $additionalTrackers[$trackerClass] = $additionalTrackerInstance;
            }
        } catch (Throwable $exception) {
            $configurationValidationErrors = $exception->getMessage();
        }

        $templateData = [
            'moduleConfiguration' => $moduleConfiguration,
            'configurationValidationErrors' => $configurationValidationErrors,
            'jobsStore' => $jobsStore,
            'defaultDataTrackerAndProvider' => $defaultDataTrackerAndProvider,
            'additionalTrackers' => $additionalTrackers,
            'setupNeeded' => $setupNeeded,
            'profilePageUri' => $this->helpersManager->getModuleRoutesHelper()
                ->getUrl(ModuleRoutesHelper::PATH_USER_PERSONAL_DATA),
        ];

        $template = new Template($this->sspConfiguration, 'accounting:admin/configuration/status.twig');

        $template->data = $templateData;
        return $template;
    }
}
