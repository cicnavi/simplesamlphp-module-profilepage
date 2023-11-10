<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Constants;

final class StateArrays
{
    public const SAML2_FULL = [
        'Responder' => [0 => '\\SimpleSAML\\Module\\saml\\IdP\\SAML2', 1 => 'sendResponse',],
        '\\SimpleSAML\\Auth\\State.exceptionFunc' => [
            0 => '\\SimpleSAML\\Module\\saml\\IdP\\SAML2',
            1 => 'handleAuthError',
        ],
        '\\SimpleSAML\\Auth\\State.restartURL' => 'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/SSOService.php?spentityid=https%3A%2F%2Fpc-example.org.hr%3A9074%2Fsimplesamlphp%2Fsimplesamlphp-2-beta-git%2Fmodule.php%2Fsaml%2Fsp%2Fmetadata.php%2Fdefault-sp&RelayState=https%3A%2F%2Flocalhost.someone.from.hr%3A9074%2Fsimplesamlphp%2Fsimplesamlphp-2-beta-git%2Fmodule.php%2Fadmin%2Ftest%2Fdefault-sp&cookieTime=1660912195',
        'SPMetadata' => [
            'SingleLogoutService' => [
                0 => [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/singleLogoutService/default-sp',
                ],
            ],
            'AssertionConsumerService' => [
                0 => [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp',
                    'index' => 0,
                ],
                1 => [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
                    'Location' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp',
                    'index' => 1,
                ],
            ],
            'contacts' => [
                0 => [
                    'emailAddress' => 'example@org.hr',
                    'givenName' => 'Marko Ivančić',
                    'contactType' => 'technical',
                ],
            ],
            'entityid' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp',
            'metadata-index' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp',
            'metadata-set' => 'saml20-sp-remote',
            'name' => 'Test service',
            'description' => 'Test service description',
            'UIInfo' => [
                'DisplayName' => [
                    'en' => 'Test service UiInfo',
                ],
                'Description' => [
                    'en' => 'Test service description UiInfo',
                ],
                'Logo' => [
                    [
                        'url' => 'https://placehold.co/100x80/orange/white?text=test',
                        'height' => 80,
                        'width' => 100,
                    ],
                ],
            ],
        ],
        'saml:RelayState' => 'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/admin/test/default-sp',
        'saml:RequestId' => null,
        'saml:IDPList' => [],
        'saml:ProxyCount' => null,
        'saml:RequesterID' => null,
        'ForceAuthn' => false,
        'isPassive' => false,
        'saml:ConsumerURL' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp',
        'saml:Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        'saml:NameIDFormat' => null,
        'saml:AllowCreate' => true,
        'saml:Extensions' => null,
        'saml:AuthnRequestReceivedAt' => 1_660_912_195.505402,
        'saml:RequestedAuthnContext' => null,
        'core:IdP' => 'saml2:https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php',
        'core:SP' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp',
        'IdPMetadata' => [
            'host' => 'localhost.someone.from.hr',
            'privatekey' => 'key.pem',
            'certificate' => 'cert.pem',
            'auth' => 'example-userpass',
            'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
            'authproc' => [100 => ['class' => 'core:AttributeMap', 0 => 'name2oid',],],
            'entityid' => 'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php',
            'metadata-index' => 'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php',
            'metadata-set' => 'saml20-idp-hosted',
        ],
        'ReturnCallback' => [0 => '\\SimpleSAML\\IdP', 1 => 'postAuth',],
        'Attributes' => [
            'hrEduPersonUniqueID' => [0 => 'testuser@primjer.hr',],
            'urn:oid:0.9.2342.19200300.100.1.1' => [0 => 'testuser',],
            'urn:oid:2.5.4.4' => [0 => 'TestSurname', 1 => 'TestSurname2',],
            'urn:oid:2.5.4.42' => [0 => 'TestName',],
            'urn:oid:2.5.4.10' => [0 => 'Testna ustanova',],
            'urn:oid:2.5.4.11' => [0 => 'Testna org jedinica',],
            'hrEduPersonPersistentID' => [0 => 'da4294fb4e5746d57ab6ad88d2daf275',],
            'updatedAt' => [0 => '123456789',],
        ],
        'Authority' => 'example-userpass',
        'AuthnInstant' => 1_660_911_943,
        'Expire' => 1_660_940_743,
        'ReturnCall' => [0 => '\\SimpleSAML\\IdP', 1 => 'postAuthProc',],
        'Destination' => [
            'SingleLogoutService' => [
                0 => [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/singleLogoutService/default-sp',
                ],
            ],
            'AssertionConsumerService' => [
                0 => [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp',
                    'index' => 0,
                ],
                1 => [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
                    'Location' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp',
                    'index' => 1,
                ],
            ],
            'contacts' => [
                0 => [
                    'emailAddress' => 'example@org.hr',
                    'givenName' => 'Marko Ivančić',
                    'contactType' => 'technical',
                ],
            ],
            'entityid' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp',
            'metadata-index' => 'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp',
            'metadata-set' => 'saml20-sp-remote',
            'UIInfo' => [
                'DisplayName' => [
                    'en' => 'Test service UiInfo',
                ],
                'Description' => [
                    'en' => 'Test service description UiInfo',
                ],
                'Logo' => [
                    [
                        'url' => 'https://placehold.co/100x80/orange/white?text=test',
                        'height' => 80,
                        'width' => 100,
                    ],
                ],
            ],
        ],
        'Source' => [
            'host' => 'localhost.someone.from.hr',
            'privatekey' => 'key.pem',
            'certificate' => 'cert.pem',
            'auth' => 'example-userpass',
            'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
            'authproc' => [100 => ['class' => 'core:AttributeMap', 0 => 'name2oid',],],
            'entityid' => 'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php',
            'metadata-index' => 'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php',
            'metadata-set' => 'saml20-idp-hosted',
        ],
        '\\SimpleSAML\\Auth\\ProcessingChain.filters' => [],
    ];

    public const OIDC_FULL = [
        'Attributes' => [
            'hrEduPersonUniqueID' => ['testuser@primjer2.hr'],
            'uid' => ['testuser'],
            'cn' => ['TestName TestSurname'],
            'sn' => ['TestSurname', 'TestSurname2'],
            'givenName' => ['TestName'],
            'mail' => ['testusermail@primjer.hr', 'testusermail2@primjer.hr'],
            'hrEduPersonPersistentID' => ['da4294fb4e5746d57ab6ad88d2daf275'],
            'displayName' => ['testname123'],
        ],
        'Authority' => 'example-userpass',
        'AuthnInstant' => 1_677_066_265,
        'Expire' => 1_677_095_065,
        'LogoutHandlers' => [
            [
                'SimpleSAML\Module\oidc\Controller\LogoutController',
                'logoutHandler'
            ],
        ],
        'Oidc' => [
            'OpenIdProviderMetadata' => [
                'issuer' => 'http://op.host.internal:8074',
                'authorization_endpoint' => 'http://op.host.internal:8074/simplesamlphp/module.php/oidc/authorize.php',
                'token_endpoint' => 'http://op.host.internal:8074/simplesamlphp/module.php/oidc/token.php',
                'userinfo_endpoint' => 'http://op.host.internal:8074/simplesamlphp/module.php/oidc/userinfo.php',
                'end_session_endpoint' => 'http://op.host.internal:8074/simplesamlphp/module.php/oidc/logout.php',
                'jwks_uri' => 'http://op.host.internal:8074/simplesamlphp/module.php/oidc/jwks.php',
                'scopes_supported' => [
                    'openid',
                    'offline_access',
                    'profile',
                    'email',
                    'address',
                    'phone',
                ],
                'response_types_supported' => [
                    'code',
                    'token',
                    'id_token',
                    'id_token token',
                ],
                'subject_types_supported' => [
                    'public',
                ],
                'id_token_signing_alg_values_supported' => [
                    'RS256',
                ],
                'code_challenge_methods_supported' => [
                    'plain',
                    'S256',
                ],
                'token_endpoint_auth_methods_supported' => [
                    'client_secret_post',
                    'client_secret_basic',
                ],
                'request_parameter_supported' => false,
                'grant_types_supported' => [
                    'authorization_code',
                    'refresh_token',
                ],
                'claims_parameter_supported' => true,
                'acr_values_supported' => [
                    '1',
                    '0',
                ],
                'backchannel_logout_supported' => true,
                'backchannel_logout_session_supported' => true,
            ],
            'RelyingPartyMetadata' => [
                'id' => 'd1ee56b4-5258-4088-a934-66963df2bcd7',
                'name' => 'Sample RP',
                'description' => 'Sample Relying Party',
                'auth_source' => null,
                'redirect_uri' => [
                    'http://sp.host.internal:8074/callback.php',
                ],
                'scopes' => [
                    'openid',
                    'offline_access',
                    'profile',
                ],
                'is_enabled' => true,
                'is_confidential' => true,
                'owner' => null,
                'post_logout_redirect_uri' => [],
                'backchannel_logout_uri' => 'http://sp.host.internal:8074/logout.php',
                'logo_uri' => 'http://sp.host.internal:8074/logo.svg',
            ],
            'AuthorizationRequestParameters' => [
                'response_type' => 'code',
                'client_id' => 'd1ee56b4-5258-4088-a934-66963df2bcd7',
                'redirect_uri' => 'http://sp.host.internal:8074/callback.php',
                'scope' => 'openid offline_access profile',
            ],
            'Source' => [
                'entityid' => 'http://op.host.internal:8074',
            ],
            'Destination' => [
                'entityid' => 'd1ee56b4-5258-4088-a934-66963df2bcd7',
            ],
            '\SimpleSAML\Auth\State.restartURL' => 'http://op.host.internal:8074/simplesamlphp/module.php/oidc/authorize.php?response_type=code&client_id=d1ee56b4-5258-4088-a934-66963df2bcd7&redirect_uri=http%3A%2F%2Fsp.host.internal%3A8074%2Fcallback.php&scope=openid+offline_access+profile+&state=MLlISLjJMKunw0ddFv4ROuyam7Qwn6sNyBW5y9Yg&nonce=uh3nJez5y1SSch347j5PkDWEJXvCwqAI1PL1Kgi6',
        ]
    ];
}
