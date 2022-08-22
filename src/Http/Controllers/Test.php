<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
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

        $jobsStore = (new JobsStoreBuilder($this->moduleConfiguration))->build();

        $this->logger->error('Accounting error test');
        $needsSetup = $jobsStore->needsSetup();
        $setupRan = false;


        if ($needsSetup) {
            $jobsStore->runSetup();
            $setupRan = true;
        }

        $template->data = [
            'test' => Translate::noop('Accounting'),
            'jobs_store' => $this->moduleConfiguration->getJobsStoreClass(),
            'jobs_store_needs_setup' => $needsSetup ? 'yes' : 'no',
            'setup_ran' => $setupRan ? 'yes' : 'no',
        ];

        return $template;
    }
}
