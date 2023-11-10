<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Http\Controllers;

use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\profilepage\Helpers\Routes;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-suppress UnusedClass Used as route controller.
 */
class Logout
{
    protected string $defaultAuthenticationSource;
    protected Simple $authSimple;
    protected HelpersManager $helpersManager;

    /**
     * @param Simple|null $authSimple
     * @param HelpersManager|null $helpersManager
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        protected SspConfiguration $sspConfiguration,
        Session $session,
        Simple $authSimple = null,
        HelpersManager $helpersManager = null
    ) {
        $this->defaultAuthenticationSource = $moduleConfiguration->getDefaultAuthenticationSource();
        $this->authSimple = $authSimple ?? new Simple($this->defaultAuthenticationSource, $sspConfiguration, $session);

        $this->helpersManager = $helpersManager ?? new HelpersManager();
    }

    public function logout(): Response
    {
        return new RunnableResponse([$this->authSimple, 'logout'], [$this->getLoggedOutUrl()]);
    }

    /**
     * @throws ConfigurationError
     */
    public function loggedOut(): Response
    {
        return new Template($this->sspConfiguration, 'profilepage:logged-out.twig');
    }

    protected function getLoggedOutUrl(): string
    {
        return $this->helpersManager->getRoutes()->getUrl(Routes::PATH_LOGGED_OUT);
    }
}
