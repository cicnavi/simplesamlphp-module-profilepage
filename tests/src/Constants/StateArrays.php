<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Constants;

final class StateArrays
{
    public const FULL = [
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
        'saml:AuthnRequestReceivedAt' => 1660912195.505402,
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
            'urn:oid:2.5.4.3' => [0 => 'TestName TestSurname',],
            'urn:oid:2.5.4.4' => [0 => 'TestSurname', 1 => 'TestSurname2',],
            'urn:oid:2.5.4.42' => [0 => 'TestName',],
            'urn:oid:0.9.2342.19200300.100.1.3' => [0 => 'testusermail@primjer.hr', 1 => 'testusermail2@primjer.hr',],
            'urn:oid:2.5.4.20' => [0 => '123',],
            'hrEduPersonExtensionNumber' => [0 => '123',],
            'urn:oid:0.9.2342.19200300.100.1.41' => [0 => '123',],
            'urn:oid:2.5.4.23' => [0 => '123',],
            'hrEduPersonUniqueNumber' => [0 => 'LOCAL_NO: 100 ', 1 => 'OIB: 00861590360',],
            'hrEduPersonOIB' => [0 => '00861590360',],
            'hrEduPersonDateOfBirth' => [0 => '20200101',],
            'hrEduPersonGender' => [0 => '9',],
            'urn:oid:0.9.2342.19200300.100.1.60' => [0 => '987654321',],
            'urn:oid:2.5.4.36' => [0 => 'cert-value',],
            'urn:oid:1.3.6.1.4.1.250.1.57' => [0 => 'http://www.org.hr/~ivek Home Page',],
            'hrEduPersonProfessionalStatus' => [0 => 'VSS',],
            'hrEduPersonAcademicStatus' => [0 => 'redoviti profesor',],
            'hrEduPersonScienceArea' => [0 => 'sociologija',],
            'hrEduPersonAffiliation' => [0 => 'djelatnik', 1 => 'student',],
            'hrEduPersonPrimaryAffiliation' => [0 => 'djelatnik',],
            'hrEduPersonStudentCategory' => [0 => 'redoviti student:diplomski sveučilišni studij',],
            'hrEduPersonExpireDate' => [0 => '20211231',],
            'hrEduPersonTitle' => [0 => 'rektor',],
            'hrEduPersonRole' => [0 => 'ICT koordinator', 1 => 'ISVU koordinator',],
            'hrEduPersonStaffCategory' => [0 => 'nastavno osoblje', 1 => 'istraživači',],
            'hrEduPersonGroupMember' => [0 => 'grupa 1', 1 => 'grupa 2',],
            'urn:oid:2.5.4.10' => [0 => 'Testna ustanova',],
            'hrEduPersonHomeOrg' => [0 => 'primjer.hr',],
            'urn:oid:2.5.4.11' => [0 => 'Testna org jedinica',],
            'urn:oid:0.9.2342.19200300.100.1.6' => [0 => '123',],
            'urn:oid:2.5.4.16' => [0 => 'Testna ustanova, Baštijanova 52A, HR-10000 Zagreb',],
            'urn:oid:2.5.4.7' => [0 => 'Zagreb',],
            'urn:oid:2.5.4.17' => [0 => 'HR-10000',],
            'urn:oid:2.5.4.9' => [0 => 'Baštijanova 52A',],
            'urn:oid:0.9.2342.19200300.100.1.39' => [0 => 'Testna adresa 52A, HR-10000, Zagreb',],
            'urn:oid:0.9.2342.19200300.100.1.20' => [0 => '123',],
            'hrEduPersonCommURI' => [0 => '123',],
            'hrEduPersonPrivacy' => [0 => 'street', 1 => 'postalCode',],
            'hrEduPersonPersistentID' => [0 => 'da4294fb4e5746d57ab6ad88d2daf275',],
            'urn:oid:2.16.840.1.113730.3.1.241' => [0 => 'testname123',],
            'urn:oid:1.3.6.1.4.1.25178.1.2.12' => [0 => 'skype: pepe.perez',],
            'hrEduPersonCardNum' => [0 => '123',],
            'updatedAt' => [0 => '123456789',],
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.7' => [0 => 'urn:example:oidc:manage:client',],
        ],
        'Authority' => 'example-userpass',
        'AuthnInstant' => 1660911943,
        'Expire' => 1660940743,
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
}