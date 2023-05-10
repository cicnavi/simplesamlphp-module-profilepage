<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers;

use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\accounting\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\accounting\Data\Providers\Interfaces\ActivityInterface;
use SimpleSAML\Module\accounting\Data\Providers\Interfaces\DataProviderInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc;
use SimpleSAML\Module\accounting\Entities\ConnectedService;
use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Helpers\Attributes;
use SimpleSAML\Module\accounting\Helpers\ProviderResolver;
use SimpleSAML\Module\accounting\Helpers\Routes;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\ModuleConfiguration\ConnectionType;
use SimpleSAML\Module\accounting\Services\AlertsBag;
use SimpleSAML\Module\accounting\Services\CsrfToken;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\MenuManager;
use SimpleSAML\Module\accounting\Services\SspModuleManager;
use SimpleSAML\Module\oidc\Services\OidcOpenIdProviderMetadataService;
use SimpleSAML\Session;
use SimpleSAML\Utils\Config\Metadata;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-suppress UnusedClass Used as route controller.
 */
class Logout
{
    protected string $defaultAuthenticationSource;
    protected Simple $authSimple;
    protected HelpersManager $helpersManager;
    protected SspConfiguration $sspConfiguration;

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param SspConfiguration $sspConfiguration
     * @param Session $session
     * @param Simple|null $authSimple
     * @param HelpersManager|null $helpersManager
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        SspConfiguration $sspConfiguration,
        Session $session,
        Simple $authSimple = null,
        HelpersManager $helpersManager = null
    ) {
        $this->defaultAuthenticationSource = $moduleConfiguration->getDefaultAuthenticationSource();
        $this->sspConfiguration = $sspConfiguration;
        $this->authSimple = $authSimple ?? new Simple($this->defaultAuthenticationSource, $sspConfiguration, $session);

        $this->helpersManager = $helpersManager ?? new HelpersManager();
    }

    public function logout(): Response
    {
        return new RunnableResponse([$this->authSimple, 'logout'], [$this->getLoggedOutUrl()]);
    }

    public function loggedOut(): Response
    {
        return new Template($this->sspConfiguration, 'accounting:logged-out.twig');
    }

    protected function getLoggedOutUrl(): string
    {
        return $this->helpersManager->getRoutes()->getUrl(Routes::PATH_LOGGED_OUT);
    }
}
