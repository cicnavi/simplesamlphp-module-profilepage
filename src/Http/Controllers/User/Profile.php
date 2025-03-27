<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Http\Controllers\User;

use DateTimeInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\profilepage\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\profilepage\Entities\Activity;
use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Oidc;
use SimpleSAML\Module\profilepage\Entities\ConnectedService;
use SimpleSAML\Module\profilepage\Entities\User;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\Factories\FactoryManager;
use SimpleSAML\Module\profilepage\Helpers\Attributes;
use SimpleSAML\Module\profilepage\Helpers\Routes;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\AlertsBag;
use SimpleSAML\Module\profilepage\Services\CsrfToken;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\MenuManager;
use SimpleSAML\Module\profilepage\Services\SspModuleManager;
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
    protected const KEY_ATTRIBUTE = 'attribute';
    protected const KEY_VALUES = 'values';
    protected const KEY_NAME = 'name';
    protected const KEY_NUMBER_OF_ACCESS = 'numberOfAccess';
    protected const KEY_LAST_ACCESS = 'lastAccess';
    protected const KEY_ENTITY_ID = 'entityId';
    protected const KEY_SERVICE_DETAILS = 'serviceDetails';
    protected const KEY_DESCRIPTION = 'description';
    protected const KEY_LOGIN_DETAILS = 'loginDetails';
    protected const KEY_FIRST_ACCESS = 'firstAccess';
    protected const KEY_ACCESS_TOKENS = 'accessTokens';
    protected const KEY_EXPIRES_AT = 'expiresAt';
    protected const KEY_REFRESH_TOKENS = 'refreshTokens';
    protected const KEY_TIME = 'time';
    protected const KEY_SERVICE_NAME = 'serviceName';
    protected const KEY_SERVICE_ENTITY_ID = 'serviceEntityId';
    protected const KEY_SENT_DATA = 'sentData';
    protected const KEY_IP_ADDRESS = 'ipAddress';
    protected const KEY_AUTHENTICATION_PROTOCOL = 'authenticationProtocol';
    protected const KEY_INFORMATION_TRANSFERRED = 'informationTransferred';

    protected string $defaultAuthenticationSource;
    protected Simple $authSimple;
    protected DataProviderBuilder $dataProviderBuilder;
    protected HelpersManager $helpersManager;
    protected SspModuleManager $sspModuleManager;
    protected User $user;
    protected MenuManager $menuManager;
    protected CsrfToken $csrfToken;
    protected AlertsBag $alertsBag;
    protected FactoryManager $factoryManager;

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
        AlertsBag $alertsBag = null,
        FactoryManager $factoryManager = null,
        protected readonly Client $httpClient = new Client(),
    ) {
        $this->defaultAuthenticationSource = $moduleConfiguration->getDefaultAuthenticationSource();
        $this->authSimple = $authSimple ?? new Simple($this->defaultAuthenticationSource, $sspConfiguration, $session);

        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->dataProviderBuilder = $authenticationDataProviderBuilder ?? new DataProviderBuilder(
            $this->moduleConfiguration,
            $this->logger,
            $this->helpersManager
        );

        $this->sspModuleManager = $sspModuleManager ?? new SspModuleManager($this->logger, $this->helpersManager);
        $this->factoryManager = $factoryManager ?? new FactoryManager();

        // Make sure the end user is authenticated.
        $this->authSimple->requireAuth();
        $this->user = $this->factoryManager->userFactory()->build($this->authSimple->getAttributes());
        $this->menuManager = $this->prepareMenuManager();
        $this->csrfToken = $csrfToken ?? new CsrfToken($this->session, $this->helpersManager);
        $this->alertsBag = $alertsBag ?? new AlertsBag($this->session);
    }

    /**
     * @throws ConfigurationError
     */
    public function personalData(): Response
    {
        $normalizedAttributes = $this->normalizeUserAttributes($this->user->getAttributes());

        $columnNames = $this->getPersonalDataColumnNames();
        $csvUrl = 'personal-data/csv';

        // TODO mivanci Delete if not necessary (Sphereon Credential Offer).
        $shpereonCredentialOffersUrl =
            ((string)$this->moduleConfiguration->get(ModuleConfiguration::OPTION_SPHEREON_BASE_URL)) .
            'oid4vci/webapp/credential-offers';

        // Just to have something to work with, as daily value based on all attributes.
        $preAuthCodeValue = hash(
            'sha256',
            var_export($normalizedAttributes, true) ^ date('Ymd'),
        );

        // We'll hardcode this, as this is for demo only.
        $sphereonResponse = $this->httpClient->post(
            $shpereonCredentialOffersUrl,
            [
                'json' => [
                    "offerMode" => "VALUE",
                    "credential_configuration_ids" => [
                        "EduPersonCredential",
                    ],
                    "grants" => [
                        "urn:ietf:params:oauth:grant-type:pre-authorized_code" => [
                            "pre-authorized_code" => $preAuthCodeValue,
                        ]
                    ],
                    "qrCodeOpts" => [],
                    "credentialDataSupplierInput" => [
                        "userId" => $normalizedAttributes["uid"] ?? 'N/A',
                        "givenName" => $normalizedAttributes["givenName"] ?? 'N/A',
                        "familyName" => $normalizedAttributes["sn"] ?? 'N/A',
                        "affiliation" => $normalizedAttributes["eduPersonAffiliation"] ?? 'N/A',
                        "organizationName" => $normalizedAttributes["o"] ?? 'N/A',
                    ],
                ],
            ],
        );

        /** @var array $decodedSphereonResponse */
        $decodedSphereonResponse = json_decode(
            $sphereonResponse->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $template = $this->resolveTemplate('profilepage:user/personal-data.twig');
        $template->data += compact(
            'normalizedAttributes',
            'columnNames',
            'csvUrl',
            'decodedSphereonResponse',
        );

        return $template;
    }

    public function personalDataCsv(): Response
    {
        $normalizedAttributes = $this->normalizeUserAttributes($this->user->getAttributes());

        $columnNames = $this->getPersonalDataColumnNames();

        /** @psalm-suppress DuplicateArrayKey No string keys are being used here. */
        $csvData = [
            [$columnNames[self::KEY_ATTRIBUTE], $columnNames[self::KEY_VALUES]],
            ...(array_map(null, array_keys($normalizedAttributes), $normalizedAttributes))
        ];

        /** @var array<array<string>> $csvData */
        return $this->csvResponseFor($csvData, __FUNCTION__ . '.csv');
    }

    protected function normalizeUserAttributes(array $attributes): array
    {
        $normalizedAttributes = [];

        $toNameAttributeMap = $this->prepareToNameAttributeMap();

        /**
         * @var string $name
         * @var string[] $value
         */
        foreach ($attributes as $name => $value) {
            // Convert attribute names to user-friendly names.
            if (array_key_exists($name, $toNameAttributeMap)) {
                $name = (string)$toNameAttributeMap[$name];
            }
            $normalizedAttributes[$name] = implode('; ', $value);
        }

        return $normalizedAttributes;
    }

    /**
     * @return array<string,string>
     */
    protected function getPersonalDataColumnNames(): array
    {
        return [
            self::KEY_ATTRIBUTE => Translate::noop('Attribute'),
            self::KEY_VALUES => Translate::noop('Values'),
        ];
    }

    /**
     * @throws Exception
     * @throws ConfigurationError
     * @throws \Exception
     */
    public function connectedOrganizations(): Response
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

        $columnNames = $this->getConnectedOrganizationColumnNames();
        $csvUrl = 'connected-organizations/csv';

        $oidc = $this->sspModuleManager->getOidc();
        $accessTokensByClient = [];
        $refreshTokensByClient = [];
        $oidcProtocolDesignation = Oidc::DESIGNATION;

        // If oidc module is enabled, gather users access and refresh tokens for particular OIDC service providers.
        if ($oidc?->isEnabled()) {
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

        $template = $this->resolveTemplate('profilepage:user/connected-organizations.twig');
        $template->data += compact(
            'connectedServiceProviderBag',
            'accessTokensByClient',
            'refreshTokensByClient',
            'oidcProtocolDesignation',
            'columnNames',
            'csvUrl'
        );

        return $template;
    }

    /**
     * @throws Exception
     * @throws ConfigurationError
     * @throws \Exception
     */
    public function connectedOrganizationsCsv(): Response
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

        $columnNames = $this->getConnectedOrganizationColumnNames();

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

    /**
     * @return array<string,string>
     */
    protected function getConnectedOrganizationColumnNames(): array
    {
        return [
            self::KEY_NAME => Translate::noop('Name'),
            self::KEY_NUMBER_OF_ACCESS => Translate::noop('Number of access'),
            self::KEY_LAST_ACCESS => Translate::noop('Last access'),
            self::KEY_ENTITY_ID => Translate::noop('Entity ID'),
            self::KEY_SERVICE_DETAILS => Translate::noop('Service details'),
            self::KEY_DESCRIPTION => Translate::noop('Description'),
            self::KEY_LOGIN_DETAILS => Translate::noop('Login details'),
            self::KEY_FIRST_ACCESS => Translate::noop('First access'),
            self::KEY_ACCESS_TOKENS => Translate::noop('Access Tokens'),
            self::KEY_EXPIRES_AT => Translate::noop('Expires at'),
            self::KEY_REFRESH_TOKENS => Translate::noop('Refresh Tokens'),
        ];
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

        $columnNames = $this->getActivityColumnNames();
        $csvUrl = 'activity/csv';

        $template = $this->resolveTemplate('profilepage:user/activity.twig');
        $template->data += compact('activityBag', 'page', 'maxResults', 'csvUrl', 'columnNames');

        return $template;
    }

    /**
     * @throws Exception
     * @throws ConfigurationError
     */
    public function activityCsv(): Response
    {
        $userIdentifier = $this->resolveUserIdentifier();

        $activityProviderClass = $this->moduleConfiguration->getActivityProviderClass();

        if (is_null($activityProviderClass)) {
            return new RedirectResponse($this->helpersManager->getRoutes()->getUrl(Routes::PATH_USER_PERSONAL_DATA));
        }

        $activityDataProvider = $this->dataProviderBuilder->buildActivityProvider($activityProviderClass);

        $activityBag = $activityDataProvider->getActivity($userIdentifier);

        $columnNames = $this->getActivityColumnNames();

        /** @psalm-suppress DuplicateArrayKey No string keys are being used here. */
        $csvData = [
            [
                $columnNames['time'],
                $columnNames['serviceName'],
                $columnNames['serviceEntityId'],
                $columnNames['ipAddress'],
                $columnNames['authenticationProtocol'],
                $columnNames['informationTransferred'] . ' (data may be truncated)',
            ],
            ...(array_map(
                fn(Activity $activity): array => [
                    $activity->getHappenedAt()->format(DateTimeInterface::RFC3339),
                    $activity->getServiceProvider()->getName() ?? '',
                    $activity->getServiceProvider()->getEntityId(),
                    $activity->getClientIpAddress() ?? '',
                    $activity->getAuthenticationProtocolDesignation() ?? '',
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
                    ), 0, 256),
                ],
                $activityBag->getAll()
            )
            )
        ];

        /** @var array<array<string>> $csvData */
        return $this->csvResponseFor($csvData, __FUNCTION__ . '.csv');
    }

    /**
     * @return array<string,string>
     */
    protected function getActivityColumnNames(): array
    {
        return [
            self::KEY_TIME => Translate::noop('Time'),
            self::KEY_SERVICE_NAME => Translate::noop('Service'),
            self::KEY_SERVICE_ENTITY_ID => Translate::noop('Service entity ID'),
            self::KEY_SENT_DATA => Translate::noop('Sent data'),
            self::KEY_IP_ADDRESS => Translate::noop('IP address'),
            self::KEY_AUTHENTICATION_PROTOCOL => Translate::noop('Authentication protocol'),
            self::KEY_INFORMATION_TRANSFERRED => Translate::noop('Information transferred to service'),
        ];
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
        if (! $oidc?->isEnabled()) {
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
        $menuManager = $this->factoryManager->menuManagerFactory()->build();

        $menuManager->addItems(
            $menuManager->buildItem(
                'personal-data',
                Translate::noop('Personal Data'),
                'css/src/icons/prof-page.svg'
            )
        );

        // Depending on enabled functionalities, add additional menu items.
        if ($this->moduleConfiguration->getConnectedServicesProviderClass() !== null) {
            $menuManager->addItems(
                $menuManager->buildItem(
                    'connected-organizations',
                    Translate::noop('Connected Organizations'),
                    'css/src/icons/conn-orgs.svg'
                )
            );
        }

        if ($this->moduleConfiguration->getActivityProviderClass() !== null) {
            $menuManager->addItems(
                $menuManager->buildItem(
                    'activity',
                    Translate::noop('Activity'),
                    'css/src/icons/activity.svg'
                )
            );
        }

        $menuManager->addItems(
            $menuManager->buildItem(
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
     * @throws Exception
     */
    protected function csvResponseFor(array $data, string $filename = 'data.csv'): Response
    {
        $fp = fopen('php://temp', 'w');

        foreach ($data as $row) {
            fputcsv($fp, $row);
        }

        if (!is_resource($fp)) {
            throw new Exception('Error creating CSV resource.');
        }

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
        $response->headers->set('Content-Encoding', 'UTF-8');

        return $response;
    }
}
