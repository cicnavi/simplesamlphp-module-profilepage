<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers\Admin;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class Configuration
{
    protected SspConfiguration $sspConfiguration;
    protected Session $session;
    protected LoggerInterface $logger;

    /**
     * @param SspConfiguration $sspConfiguration
     * @param Session $session The current user session.
     * @param LoggerInterface $logger
     */
    public function __construct(
        SspConfiguration $sspConfiguration,
        Session $session,
        LoggerInterface $logger
    ) {
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->logger = $logger;
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

        try {
            $moduleConfiguration = new ModuleConfiguration();

            if (
                $moduleConfiguration->getAccountingProcessingType() ===
                ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS
            ) {
                $jobsStore = (new JobsStoreBuilder($moduleConfiguration, $this->logger))
                    ->build($moduleConfiguration->getJobsStoreClass());
            }
        } catch (Throwable $exception) {
            $configurationValidationErrors = $exception->getMessage();
        }

        $templateData = [
            'moduleConfiguration' => $moduleConfiguration,
            'configurationValidationErrors' => $configurationValidationErrors,
            'jobsStore' => $jobsStore,
        ];

        $template = new Template($this->sspConfiguration, 'accounting:admin/configuration/status.twig');

        $template->data = $templateData;
        return $template;
    }
}