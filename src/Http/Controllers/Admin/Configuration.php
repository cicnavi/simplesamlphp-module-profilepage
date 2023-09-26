<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers\Admin;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\accounting\Data\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Data\Trackers\Builders\DataTrackerBuilder;
use SimpleSAML\Module\accounting\Helpers\Routes;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * @psalm-suppress UnusedClass Used as route controller.
 */
class Configuration
{
    protected Utils\Auth $sspAuthUtils;

    /**
     * @throws \SimpleSAML\Error\Exception
     */
    public function __construct(
        protected SspConfiguration $sspConfiguration,
        protected Session $session,
        protected LoggerInterface $logger,
        protected HelpersManager $helpersManager,
        Utils\Auth $sspAuthUtils = null
    ) {
        $this->sspAuthUtils = $sspAuthUtils ?? new Utils\Auth();

        $this->sspAuthUtils->requireAdmin();
    }

    /**
     * @throws Exception
     */
    public function status(Request $request): Template
    {
        // Instantiate ModuleConfiguration here (instead in constructor) so we can check for validation errors.
        $moduleConfiguration = null;
        $configurationValidationErrors = null;
        $jobsStore = null;
        $providers = [];
        $additionalTrackers = [];
        $setupNeeded = false;
        $runSetup = $request->query->has('runSetup');

        try {
            $moduleConfiguration = new ModuleConfiguration();

            $dataProviderBuilder = new DataProviderBuilder($moduleConfiguration, $this->logger, $this->helpersManager);
            foreach ($moduleConfiguration->getProviderClasses() as $providerClass) {
                $providerInstance = $dataProviderBuilder->build($providerClass);
                if ($providerInstance->needsSetup()) {
                    if ($runSetup) {
                        $providerInstance->runSetup();
                    } else {
                        $setupNeeded = true;
                    }
                }
                $providers[$providerClass] = $providerInstance;
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

            $dataTrackerBuilder = new DataTrackerBuilder($moduleConfiguration, $this->logger, $this->helpersManager);
            foreach ($moduleConfiguration->getAdditionalTrackers() as $trackerClass) {
                $trackerInstance = $dataTrackerBuilder->build($trackerClass);

                if ($trackerInstance->needsSetup()) {
                    if ($runSetup) {
                        $trackerInstance->runSetup();
                    } else {
                        $setupNeeded = true;
                    }
                }
                $additionalTrackers[$trackerClass] = $trackerInstance;
            }
        } catch (Throwable $exception) {
            $configurationValidationErrors = $exception->getMessage();
        }

        $templateData = [
            'moduleConfiguration' => $moduleConfiguration,
            'configurationValidationErrors' => $configurationValidationErrors,
            'jobsStore' => $jobsStore,
            'providers' => $providers,
            'additionalTrackers' => $additionalTrackers,
            'setupNeeded' => $setupNeeded,
            'profilePageUri' => $this->helpersManager->getRoutes()
                ->getUrl(Routes::PATH_USER_PERSONAL_DATA),
            'runSetup' => $runSetup,
        ];

        $template = new Template($this->sspConfiguration, 'accounting:admin/configuration/status.twig');

        $template->data = $templateData;
        return $template;
    }
}
