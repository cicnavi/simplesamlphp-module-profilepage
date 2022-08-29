<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * TODO mivanci delete this file
 */
class Test
{
    protected SspConfiguration $sspConfiguration;
    protected Session $session;
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;

    /**
     * @param SspConfiguration $sspConfiguration
     * @param Session $session The current user session.
     * @param ModuleConfiguration $moduleConfiguration
     * @param LoggerInterface $logger
     */
    public function __construct(
        SspConfiguration $sspConfiguration,
        Session $session,
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger
    ) {
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @return Template
     * @throws Exception
     */
    public function test(Request $request): Template
    {
        $template = new Template($this->sspConfiguration, 'accounting:test.twig');

        $jobsStore = (new JobsStoreBuilder($this->moduleConfiguration, $this->logger))
            ->build($this->moduleConfiguration->getJobsStoreClass());

        $dataStore = Store::build($this->moduleConfiguration, $this->logger, Tracker::class);

        $jobsStoreNeedsSetup = $jobsStore->needsSetup();
        $jobsStoreSetupRan = false;

        $dataStoreNeedsSetup = $dataStore->needsSetup();
        $dataStoreSetupRan = false;


        if ($jobsStoreNeedsSetup && $request->query->has('setup')) {
            $this->logger->error('Jobs Store setup ran.');
            $jobsStore->runSetup();
            $jobsStoreSetupRan = true;
        }

        if ($dataStoreNeedsSetup && $request->query->has('setup')) {
            $this->logger->error('Data Store setup ran.');
            $dataStore->runSetup();
            $dataStoreSetupRan = true;
        }

        $template->data = [
            'test' => Translate::noop('Accounting'),
            'jobs_store' => $this->moduleConfiguration->getJobsStoreClass(),
            'jobs_store_needs_setup' => $jobsStoreNeedsSetup ? 'yes' : 'no',
            'jobs_store_setup_ran' => $jobsStoreSetupRan ? 'yes' : 'no',

            'data_store' => get_class($dataStore),
            'data_store_needs_setup' => $dataStoreNeedsSetup ? 'yes' : 'no',
            'data_store_setup_ran' => $dataStoreSetupRan ? 'yes' : 'no',
        ];

        return $template;
    }
}
