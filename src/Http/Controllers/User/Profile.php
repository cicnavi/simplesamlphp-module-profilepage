<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers\User;

use DateTimeInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc;
use SimpleSAML\Module\accounting\Entities\ConnectedService;
use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Helpers\Attributes;
use SimpleSAML\Module\accounting\Helpers\DateTime;
use SimpleSAML\Module\accounting\Helpers\Routes;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\AlertsBag;
use SimpleSAML\Module\accounting\Services\CsrfToken;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\MenuManager;
use SimpleSAML\Module\accounting\Services\SspModuleManager;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Stringable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-suppress UnusedClass Used as route controller.
 */
class Profile
{
    protected const KEY_CSV = 'csv';
    protected string $defaultAuthenticationSource;
    protected Simple $authSimple;
    protected DataProviderBuilder $dataProviderBuilder;
    protected HelpersManager $helpersManager;
    protected SspModuleManager $sspModuleManager;
    protected User $user;
    protected MenuManager $menuManager;
    protected CsrfToken $csrfToken;
    protected AlertsBag $alertsBag;

    /**
     * @param Session $session The current user session.
     * @param Simple|null $authSimple
     * @param DataProviderBuilder|null $authenticationDataProviderBuilder
     * @param HelpersManager|null $helpersManager
     * @param SspModuleManager|null $sspModuleManager
     * @param CsrfToken|null $csrfToken
     * @param AlertsBag|null $alertsBag
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     */
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected SspConfiguration $sspConfiguration,
        protected Session $session,
        protected LoggerInterface $logger,
        Simple $authSimple = null,
        DataProviderBuilder $authenticationDataProviderBuilder = null,
        HelpersManager $helpersManager = null,
        SspModuleManager $sspModuleManager = null,
        CsrfToken $csrfToken = null,
        AlertsBag $alertsBag = null
    ) {
        $this->defaultAuthenticationSource = $moduleConfiguration->getDefaultAuthenticationSource();
        $this->authSimple = $authSimple ?? new Simple($this->defaultAuthenticationSource, $sspConfiguration, $session);

        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->dataProviderBuilder = $authenticationDataProviderBuilder ??
            new DataProviderBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager);

        $this->sspModuleManager = $sspModuleManager ?? new SspModuleManager($this->logger, $this->helpersManager);

        // Make sure the end user is authenticated.
        $this->authSimple->requireAuth();
        $this->user = new User($this->authSimple->getAttributes());
        $this->menuManager = $this->prepareMenuManager();
        $this->csrfToken = $csrfToken ?? new CsrfToken($this->session, $this->helpersManager);
        $this->alertsBag = $alertsBag ?? new AlertsBag($this->session);
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
        foreach ($this->user->getAttributes() as $name => $value) {
            // Convert attribute names to user-friendly names.
            if (array_key_exists($name, $toNameAttributeMap)) {
                $name = (string)$toNameAttributeMap[$name];
            }
            $normalizedAttributes[$name] = implode('; ', $value);
        }

        $attributeColumnName = Translate::noop('Attribute');
        $valuesColumnName = Translate::noop('Values');

        if ($request->query->has(self::KEY_CSV)) {
            /** @psalm-suppress DuplicateArrayKey No string keys are being used here. */
            $csvData = [
                [$attributeColumnName, $valuesColumnName],
                ...(array_map(null, array_keys($normalizedAttributes), $normalizedAttributes))
            ];

            /** @var array<array<string>> $csvData */
            return $this->csvResponseFor($csvData, __FUNCTION__ . '.csv');
        }

        $template = $this->resolveTemplate('accounting:user/personal-data.twig');
        $template->data += compact('normalizedAttributes', 'attributeColumnName', 'valuesColumnName');

        return $template;
    }

    /**
     * @throws Exception
     * @throws ConfigurationError
     * @throws \Exception
     */
    public function connectedOrganizations(Request $request): Response
    {
        $userIdentifier = $this->resolveUserIdentifier();

        $connectedServiceProviderClass = $this->moduleConfiguration->getConnectedServicesProviderClass();

        if (is_null($connectedServiceProviderClass)) {
            return new RedirectResponse($this->helpersManager->getRoutes()->getUrl(Routes::PATH_USER_PERSONAL_DATA));
        }

        $connectedServicesDataProvider = $this->dataProviderBuilder->buildConnectedServicesProvider(
            $connectedServiceProviderClass
        );

        $connectedServiceProviderBag = $connectedServicesDataProvider->getConnectedServices($userIdentifier);

        $columnNames = [
            'name' => Translate::noop('Name'),
            'numberOfAccess' => Translate::noop('Number of access'),
            'lastAccess' => Translate::noop('Last access'),
            'entityId' => Translate::noop('Entity ID'),
            'serviceDetails' => Translate::noop('Service details'),
            'description' => Translate::noop('Description'),
            'loginDetails' => Translate::noop('Login details'),
            'firstAccess' => Translate::noop('First access'),
            'accessTokens' => Translate::noop('Access Tokens'),
            'expiresAt' => Translate::noop('Expires at'),
            'refreshTokens' => Translate::noop('Refresh Tokens'),
        ];

        if ($request->query->has(self::KEY_CSV)) {
            /** @psalm-suppress DuplicateArrayKey No string keys are being used here. */
            $csvData = [
                [
                    $columnNames['name'],
                    $columnNames['entityId'],
                    $columnNames['numberOfAccess'],
                    $columnNames['firstAccess'],
                    $columnNames['lastAccess'],
                ],
                ...(array_map(
                    fn(ConnectedService $connectedService): array => [
                        $connectedService->getServiceProvider()->getName() ?? '',
                        $connectedService->getServiceProvider()->getEntityId(),
                        $connectedService->getNumberOfAuthentications(),
                        $connectedService->getFirstAuthenticationAt()->format(DateTimeInterface::RFC3339),
                        $connectedService->getLastAuthenticationAt()->format(DateTimeInterface::RFC3339),
                    ],
                    $connectedServiceProviderBag->getAll()
                )
                )
            ];

            /** @var array<array<string>> $csvData */
            return $this->csvResponseFor($csvData, __FUNCTION__ . '.csv');
        }

        $oidc = $this->sspModuleManager->getOidc();
        $accessTokensByClient = [];
        $refreshTokensByClient = [];
        $oidcProtocolDesignation = Oidc::DESIGNATION;

        // If oidc module is enabled, gather users access and refresh tokens for particular OIDC service providers.
        if ($oidc->isEnabled()) {
            // Filter out OIDC service providers and get their entity (client) IDs.
            $oidcClientIds = array_map(
                fn(ConnectedService $connectedService) => $connectedService->getServiceProvider()->getEntityId(),
                array_filter(
                    $connectedServiceProviderBag->getAll(),
                    fn(ConnectedService $connectedService) =>
                        $connectedService->getServiceProvider()->getProtocol()->getDesignation() === Oidc::DESIGNATION
                )
            );

            if (! empty($oidcClientIds)) {
                $accessTokensByClient = $this->helpersManager->getArr()->groupByValue(
                    $oidc->getUsersAccessTokens($userIdentifier, $oidcClientIds),
                    'client_id'
                );

                $refreshTokensByClient = $this->helpersManager->getArr()->groupByValue(
                    $oidc->getUsersRefreshTokens($userIdentifier, $oidcClientIds),
                    'client_id'
                );
            }
        }

        $template = $this->resolveTemplate('accounting:user/connected-organizations.twig');
        $template->data += compact(
            'connectedServiceProviderBag',
            'accessTokensByClient',
            'refreshTokensByClient',
            'oidcProtocolDesignation',
            'columnNames',
        );

        return $template;
    }

    /**
     * @throws Exception
     * @throws ConfigurationError
     */
    public function activity(Request $request): Response
    {
        $userIdentifier = $this->resolveUserIdentifier();

        $activityProviderClass = $this->moduleConfiguration->getActivityProviderClass();

        if (is_null($activityProviderClass)) {
            return new RedirectResponse($this->helpersManager->getRoutes()->getUrl(Routes::PATH_USER_PERSONAL_DATA));
        }

        $activityDataProvider = $this->dataProviderBuilder->buildActivityProvider($activityProviderClass);

        $page = ($page = (int)$request->query->get('page', 1)) > 0 ? $page : 1;

        $maxResults = 10;
        $firstResult = ($page - 1) * $maxResults;

        $activityBag = $activityDataProvider->getActivity($userIdentifier, $maxResults, $firstResult);

        $columnNames = [
            'time' => Translate::noop('Time'),
            'serviceName' => Translate::noop('Service'),
            'serviceEntityId' => Translate::noop('Service entity ID'),
            'sentData' => Translate::noop('Sent data'),
            'ipAddress' => Translate::noop('IP address'),
            'authenticationProtocol' => Translate::noop('Authentication protocol'),
            'informationTransferred' => Translate::noop('Information transfered to service'),
        ];

        if ($request->query->has(self::KEY_CSV)) {
            /** @psalm-suppress DuplicateArrayKey No string keys are being used here. */
            $csvData = [
                [
                    $columnNames['time'],
                    $columnNames['serviceName'],
                    $columnNames['serviceEntityId'],
                    $columnNames['informationTransferred'] . ' (data may be truncated)',
                    $columnNames['ipAddress'],
                    $columnNames['authenticationProtocol'],
                ],
                ...(array_map(
                    fn(Activity $activity): array => [
                        $activity->getHappenedAt()->format(DateTimeInterface::RFC3339),
                        $activity->getServiceProvider()->getName() ?? '',
                        $activity->getServiceProvider()->getEntityId(),
                        substr(implode(
                            '; ',
                            array_map(
                                fn($key, array $values): string => sprintf(
                                    '%s: %s',
                                    $key,
                                    implode(', ', array_map(fn($value): string => (string)$value, $values))
                                ),
                                array_keys($activity->getUser()->getAttributes()),
                                $activity->getUser()->getAttributes()
                            )
                        ), 0, 100),
                        $activity->getClientIpAddress() ?? '',
                        $activity->getAuthenticationProtocolDesignation() ?? '',
                    ],
                    $activityBag->getAll()
                )
                )
            ];

            /** @var array<array<string>> $csvData */
            return $this->csvResponseFor($csvData, __FUNCTION__ . '.csv');
        }

        $template = $this->resolveTemplate('accounting:user/activity.twig');
        $template->data += compact('activityBag', 'page', 'maxResults');

        return $template;
    }

    /**
     * @throws Exception
     * @throws \Exception
     * @throws \Exception
     */
    public function oidcTokenRevokeXhr(Request $request): Response
    {
        $oidc = $this->sspModuleManager->getOidc();
        $response = new JsonResponse();


        // If oidc module is not enabled, this route should not be called.
        if (! $oidc->isEnabled()) {
            return new JsonResponse(['status' => 'error', 'message' => 'Not available.'], 404);
        }

        if (! $this->csrfToken->validate((string) $request->cookies->get(CsrfToken::KEY))) {
            $this->appendCsrfCookie($response);
            return $response
                ->setData(['status' => 'error', 'message' => 'CSRF validation failed.'])
                ->setStatusCode(400);
        }

        $this->appendCsrfCookie($response);

        $validTokenTypes = ['access', 'refresh'];

        $tokenType = $request->request->getAlnum('token-type');

        if (! in_array($tokenType, $validTokenTypes)) {
            return $response
                ->setData(['status' => 'error', 'message' => 'Token type not valid.'])
                ->setStatusCode(422);
        }

        $tokenId = $request->request->getAlnum('token-id');

        $userIdentifier = $this->resolveUserIdentifier();

        if ($tokenType === 'access') {
            $oidc->revokeUsersAccessToken($userIdentifier, $tokenId);
        } elseif ($tokenType === 'refresh') {
            $oidc->revokeUsersRefreshToken($userIdentifier, $tokenId);
        }

        return $response
            ->setData(['status' => 'success', 'message' => 'Token revoked successfully.']);
    }

    /**
     * @throws Exception
     */
    protected function resolveUserIdentifier(): string
    {
        $userIdAttributeName = $this->moduleConfiguration->getUserIdAttributeName();
        $userIdentifier = $this->user->getFirstAttributeValue($userIdAttributeName);

        if (is_null($userIdentifier)) {
            $message = sprintf('No identifier %s present in user attributes.', $userIdAttributeName);
            throw new Exception($message);
        }

        return $userIdentifier;
    }

    /**
     * Load all attribute map files which translate attribute names to user-friendly name format.
     */
    protected function prepareToNameAttributeMap(): array
    {
        return $this->helpersManager->getAttributes()->getMergedAttributeMapForFiles(
            $this->sspConfiguration->getBaseDir(),
            Attributes::MAP_FILES_TO_NAME
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

        $templateInstance->data = [
            'menuManager' => $this->menuManager,
            'csrfToken' => $this->csrfToken,
            'alertsBag' => $this->alertsBag,
            'actionButtonsEnabled' => $this->moduleConfiguration->getActionButtonsEnabled()
        ];

        // Make CSRF token also available as a cookie, so it can be used for XHR POST requests validation.
        $this->appendCsrfCookie($templateInstance);

        return $templateInstance;
    }

    protected function prepareMenuManager(): MenuManager
    {
        $menuManager = new MenuManager();

        $menuManager->addItems(
            new MenuManager\MenuItem(
                'personal-data',
                Translate::noop('Personal Data'),
                'css/src/icons/prof-page.svg'
            )
        );

        // Depending on enabled functionalities, add additional menu items.
        if ($this->moduleConfiguration->getConnectedServicesProviderClass() !== null) {
            $menuManager->addItems(
                new MenuManager\MenuItem(
                    'connected-organizations',
                    Translate::noop('Connected Organizations'),
                    'css/src/icons/conn-orgs.svg'
                )
            );
        }

        if ($this->moduleConfiguration->getActivityProviderClass() !== null) {
            $menuManager->addItems(
                new MenuManager\MenuItem(
                    'activity',
                    Translate::noop('Activity'),
                    'css/src/icons/activity.svg'
                )
            );
        }

        $menuManager->addItems(
            new MenuManager\MenuItem(
                'logout',
                Translate::noop('Log out'),
                'css/src/icons/logout.svg'
            )
        );

        return $menuManager;
    }

    protected function appendCsrfCookie(Response $response): void
    {
        $response->headers->setCookie(new Cookie(CsrfToken::KEY, $this->csrfToken->get()));
    }

    /**
     * @param array<array<array-key, Stringable|null|scalar>> $data
     */
    protected function csvResponseFor(array $data, string $filename = 'data.csv'): Response
    {
        $fp = fopen('php://output', 'w');

        foreach ($data as $row) {
            fputcsv($fp, $row);
        }

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }
}
