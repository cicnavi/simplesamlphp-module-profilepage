<?php

namespace SimpleSAML\Module\accounting\Http\Controllers\User;

use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Builders\AuthenticationDataProviderBuilder;
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

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param SspConfiguration $sspConfiguration
     * @param Session $session The current user session.
     * @param LoggerInterface $logger
     * @param Simple|null $authSimple
     * @param AuthenticationDataProviderBuilder|null $authenticationDataProviderBuilder
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        SspConfiguration $sspConfiguration,
        Session $session,
        LoggerInterface $logger,
        Simple $authSimple = null,
        AuthenticationDataProviderBuilder $authenticationDataProviderBuilder = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->logger = $logger;

        $this->defaultAuthenticationSource = $moduleConfiguration->getDefaultAuthenticationSource();
        $this->authSimple = $authSimple ?? new Simple($this->defaultAuthenticationSource, $sspConfiguration, $session);

        $this->authenticationDataProviderBuilder = $authenticationDataProviderBuilder ??
            new AuthenticationDataProviderBuilder($this->moduleConfiguration, $this->logger);
    }

    public function personalData(Request $request): Template
    {
        $this->authSimple->requireAuth();

        $normalizedAttributes = [];

        foreach ($this->authSimple->getAttributes() as $name => $value) {
            $normalizedAttributes[$name] = is_array($value) ? implode('; ', $value) : (string)$value;
        }
        die(var_dump($normalizedAttributes));
        $template = new Template($this->sspConfiguration, 'accounting:user/personal-data.twig');
        $template->data = compact('normalizedAttributes');
        return $template;
    }

    public function connectedOrganizations(Request $request): Template
    {
        // TODO mivanci make sure to use slave connection for data provider if available
        $this->authSimple->requireAuth();
        $attributes = $this->authSimple->getAttributes();
        $idAttributeName = $this->moduleConfiguration->getUserIdAttributeName();

        if (empty($attributes[$idAttributeName]) || !is_array($attributes[$idAttributeName])) {
            $message = sprintf('No identifier %s present in user attributes.', $idAttributeName);
            throw new Module\accounting\Exceptions\Exception($message);
        }

        $userIdentifier = (string)reset($attributes[$idAttributeName]);

        $authenticationDataProvider = $this->authenticationDataProviderBuilder
            ->build($this->moduleConfiguration->getDefaultDataTrackerAndProviderClass());

        var_dump($authenticationDataProvider->getConnectedOrganizations($userIdentifier));
        die();
        $data = [];
        $template = new Template($this->sspConfiguration, 'accounting:user/personal-data.twig');
        $template->data = compact('data');
        return $template;
    }

    public function activity(): Template
    {
        $this->authSimple->requireAuth();
        $attributes = $this->authSimple->getAttributes();
        $idAttributeName = $this->moduleConfiguration->getUserIdAttributeName();

        if (empty($attributes[$idAttributeName]) || !is_array($attributes[$idAttributeName])) {
            $message = sprintf('No identifier %s present in user attributes.', $idAttributeName);
            throw new Module\accounting\Exceptions\Exception($message);
        }

        $userIdentifier = (string)reset($attributes[$idAttributeName]);

        $authenticationDataProvider = $this->authenticationDataProviderBuilder
            ->build($this->moduleConfiguration->getDefaultDataTrackerAndProviderClass());

        var_dump($authenticationDataProvider->getActivity($userIdentifier));
        die();
        $data = [];
        $template = new Template($this->sspConfiguration, 'accounting:user/personal-data.twig');
        $template->data = compact('data');
        return $template;
    }
}
