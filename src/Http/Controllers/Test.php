<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * TODO mivanci delete this file before release
 * @psalm-suppress all
 */
class Test
{
    protected SspConfiguration $sspConfiguration;
    protected Session $session;
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;

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
        LoggerInterface $logger,
        HelpersManager $helpersManager
    ) {
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
        $this->helpersManager = $helpersManager;
    }

    /**
     * @param Request $request
     * @return Template
     * @throws Exception
     */
    public function test(Request $request): Template
    {
        $template = new Template($this->sspConfiguration, 'accounting:test.twig');

        $retentionPolicy = new \DateInterval('P4D');

        (new AuthenticationDataTrackerBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager))
            ->build($this->moduleConfiguration->getDefaultDataTrackerAndProviderClass())
            ->enforceDataRetentionPolicy($retentionPolicy);

        die('end');

        return $template;
    }
}
