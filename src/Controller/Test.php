<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

class Test
{
    protected Configuration $configuration;
    protected Session $session;
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;

    /**
     * @param Configuration $configuration
     * @param Session $session The current user session.
     * @param ModuleConfiguration $moduleConfiguration
     * @param LoggerInterface $logger
     */
    public function __construct(
        Configuration $configuration,
        Session $session,
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $loggerkita
    ) {
        $this->configuration = $configuration;
        $this->session = $session;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $loggerkita;
    }

    /**
     * @param Request $request
     * @return Template
     * @throws Exception
     */
    public function test(Request $request): Template
    {
        $template = new Template($this->configuration, 'accounting:configuration.twig');

        $jobsStore = (new JobsStoreBuilder($this->moduleConfiguration))->build();

//        throw new \SimpleSAML\Error\Exception('test');
        $this->logger->error('Accounting errsr test');
        var_dump($this->logger);
        die();
        $needsSetup = $jobsStore->needsSetup();
        $setupRan = false;


        if ($needsSetup) {
            $jobsStore->runSetup();
            $setupRan = true;
        }

        $template->data = [
            'test' => Translate::noop('Accounting'),
            'jobs_store' => $this->moduleConfiguration->getJobsStore(),
            'jobs_store_needs_setup' => $needsSetup ? 'yes' : 'no',
            'setup_ran' => $setupRan ? 'yes' : 'no',
        ];

        return $template;
    }
}
