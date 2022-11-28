<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers\User;

use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Helpers\AttributesHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\ModuleConfiguration\ConnectionType;
use SimpleSAML\Module\accounting\Providers\Builders\AuthenticationDataProviderBuilder;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Profile
{
    protected ModuleConfiguration $moduleConfiguration;
    protected SspConfiguration $sspConfiguration;
    protected Session $session;
    protected LoggerInterface $logger;
    protected string $defaultAuthenticationSource;
    protected Simple $authSimple;
    protected AuthenticationDataProviderBuilder $authenticationDataProviderBuilder;
    protected HelpersManager $helpersManager;

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param SspConfiguration $sspConfiguration
     * @param Session $session The current user session.
     * @param LoggerInterface $logger
     * @param Simple|null $authSimple
     * @param AuthenticationDataProviderBuilder|null $authenticationDataProviderBuilder
     * @param HelpersManager|null $helpersManager
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        SspConfiguration $sspConfiguration,
        Session $session,
        LoggerInterface $logger,
        Simple $authSimple = null,
        AuthenticationDataProviderBuilder $authenticationDataProviderBuilder = null,
        HelpersManager $helpersManager = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->logger = $logger;

        $this->defaultAuthenticationSource = $moduleConfiguration->getDefaultAuthenticationSource();
        $this->authSimple = $authSimple ?? new Simple($this->defaultAuthenticationSource, $sspConfiguration, $session);

        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->authenticationDataProviderBuilder = $authenticationDataProviderBuilder ??
            new AuthenticationDataProviderBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager);

        // Make sure the end user is authenticated.
        $this->authSimple->requireAuth();
    }

    /**
     * @throws ConfigurationError
     */
    public function personalData(Request $request): Response
    {
        $normalizedAttributes = [];

        $toNameAttributeMap = $this->prepareToNameAttributeMap();

        /**
         * @var string $name
         * @var string[] $value
         */
        foreach ($this->authSimple->getAttributes() as $name => $value) {
            // Convert attribute names to user-friendly names.
            if (array_key_exists($name, $toNameAttributeMap)) {
                $name = (string)$toNameAttributeMap[$name];
            }
            $normalizedAttributes[$name] = implode('; ', $value);
        }

        $template = $this->resolveTemplate('accounting:user/personal-data.twig');
        $template->data = compact('normalizedAttributes');

        return $template;
    }

    /**
     * @throws Exception
     * @throws ConfigurationError
     */
    public function connectedOrganizations(Request $request): Template
    {
        $userIdentifier = $this->resolveUserIdentifier();

        $authenticationDataProvider = $this->resolveAuthenticationDataProvider();

        $connectedServiceProviderBag = $authenticationDataProvider->getConnectedServiceProviders($userIdentifier);

        $template = $this->resolveTemplate('accounting:user/connected-organizations.twig');
        $template->data = compact('connectedServiceProviderBag');

        return $template;
    }

    /**
     * @throws Exception
     * @throws ConfigurationError
     */
    public function activity(Request $request): Template
    {
        $userIdentifier = $this->resolveUserIdentifier();

        $authenticationDataProvider = $this->resolveAuthenticationDataProvider();

        $page = ($page = (int)$request->query->get('page', 1)) > 0 ? $page : 1;

        $maxResults = 10;
        $firstResult = ($page - 1) * $maxResults;

        $activityBag = $authenticationDataProvider->getActivity($userIdentifier, $maxResults, $firstResult);

        $template = $this->resolveTemplate('accounting:user/activity.twig');
        $template->data = compact('activityBag', 'page', 'maxResults');

        return $template;
    }

    /**
     * @throws Exception
     */
    protected function resolveUserIdentifier(): string
    {
        $attributes = $this->authSimple->getAttributes();
        $idAttributeName = $this->moduleConfiguration->getUserIdAttributeName();

        if (empty($attributes[$idAttributeName]) || !is_array($attributes[$idAttributeName])) {
            $message = sprintf('No identifier %s present in user attributes.', $idAttributeName);
            throw new Exception($message);
        }

        return (string)reset($attributes[$idAttributeName]);
    }

    /**
     * @throws Exception
     */
    protected function resolveAuthenticationDataProvider(): AuthenticationDataProviderInterface
    {
        return $this->authenticationDataProviderBuilder
            ->build(
                $this->moduleConfiguration->getDefaultDataTrackerAndProviderClass(),
                ConnectionType::SLAVE
            );
    }

    public function logout(): Response
    {
        return new RunnableResponse([$this->authSimple, 'logout'], [$this->getLogoutUrl()]);
    }

    protected function getLogoutUrl(): string
    {
        try {
            return $this->sspConfiguration->getBasePath() . 'logout.php';
        } catch (CriticalConfigurationError $exception) {
            $message = \sprintf('Could not resolve SimpleSAMLphp base path. Error was: %s', $exception->getMessage());
            throw new InvalidConfigurationException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * Load all attribute map files which translate attribute names to user-friendly name format.
     */
    protected function prepareToNameAttributeMap(): array
    {
        return $this->helpersManager->getAttributesHelper()->getMergedAttributeMapForFiles(
            $this->sspConfiguration->getBaseDir(),
            AttributesHelper::MAP_FILES_TO_NAME
        );
    }

    /**
     * @throws ConfigurationError
     */
    protected function resolveTemplate(string $template): Template
    {
        $templateInstance = new Template($this->sspConfiguration, $template);

        $templateInstance->getLocalization()->addModuleDomain(ModuleConfiguration::MODULE_NAME);
        $templateInstance->getLocalization()->addAttributeDomains();

        return $templateInstance;
    }
}
