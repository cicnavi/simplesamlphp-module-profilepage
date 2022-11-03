<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Constants;

use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;

class RawRowResult
{
    public const CONNECTED_ORGANIZATION = [
        TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS => 1,
        TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT => '2022-02-22 22:22:22',
        TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT => '2022-02-02 22:22:22',
        TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA => 'a:9:{s:19:"SingleLogoutService";a:1:{i:0;a:2:{s:7:"Binding";s:50:"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect";s:8:"Location";s:121:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/singleLogoutService/default-sp";}}s:24:"AssertionConsumerService";a:2:{i:0;a:3:{s:7:"Binding";s:46:"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST";s:8:"Location";s:126:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp";s:5:"index";i:0;}i:1;a:3:{s:7:"Binding";s:50:"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact";s:8:"Location";s:126:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp";s:5:"index";i:1;}}s:8:"contacts";a:1:{i:0;a:3:{s:12:"emailAddress";s:15:"mivanci@srce.hr";s:9:"givenName";s:15:"Marko Ivančić";s:11:"contactType";s:9:"technical";}}s:4:"name";a:1:{s:2:"en";s:12:"Test service";}s:11:"description";a:1:{s:2:"en";s:27:"Description of test service";}s:16:"OrganizationName";a:1:{s:2:"en";s:17:"Test organization";}s:8:"entityid";s:114:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp";s:14:"metadata-index";s:114:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp";s:12:"metadata-set";s:16:"saml20-sp-remote";}',
        TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES => 'a:5:{s:33:"urn:oid:0.9.2342.19200300.100.1.1";a:1:{i:0;s:11:"student-uid";}s:32:"urn:oid:1.3.6.1.4.1.5923.1.1.1.1";a:2:{i:0;s:6:"member";i:1;s:7:"student";}s:15:"urn:oid:2.5.4.4";a:1:{i:0;s:10:"student-sn";}s:19:"hrEduPersonUniqueID";a:1:{i:0;s:19:"student@example.org";}s:23:"hrEduPersonPersistentID";a:1:{i:0;s:13:"student123abc";}}',
    ];


    public const ACTIVITY = [
        TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT => '2022-02-22 22:22:22',
        TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA => 'a:9:{s:19:"SingleLogoutService";a:1:{i:0;a:2:{s:7:"Binding";s:50:"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect";s:8:"Location";s:121:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/singleLogoutService/default-sp";}}s:24:"AssertionConsumerService";a:2:{i:0;a:3:{s:7:"Binding";s:46:"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST";s:8:"Location";s:126:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp";s:5:"index";i:0;}i:1;a:3:{s:7:"Binding";s:50:"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact";s:8:"Location";s:126:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp";s:5:"index";i:1;}}s:8:"contacts";a:1:{i:0;a:3:{s:12:"emailAddress";s:15:"mivanci@srce.hr";s:9:"givenName";s:15:"Marko Ivančić";s:11:"contactType";s:9:"technical";}}s:4:"name";a:1:{s:2:"en";s:12:"Test service";}s:11:"description";a:1:{s:2:"en";s:27:"Description of test service";}s:16:"OrganizationName";a:1:{s:2:"en";s:17:"Test organization";}s:8:"entityid";s:114:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp";s:14:"metadata-index";s:114:"https://pc-mivancic.srce.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp";s:12:"metadata-set";s:16:"saml20-sp-remote";}',
        TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES => 'a:5:{s:33:"urn:oid:0.9.2342.19200300.100.1.1";a:1:{i:0;s:11:"student-uid";}s:32:"urn:oid:1.3.6.1.4.1.5923.1.1.1.1";a:2:{i:0;s:6:"member";i:1;s:7:"student";}s:15:"urn:oid:2.5.4.4";a:1:{i:0;s:10:"student-sn";}s:19:"hrEduPersonUniqueID";a:1:{i:0;s:19:"student@example.org";}s:23:"hrEduPersonPersistentID";a:1:{i:0;s:13:"student123abc";}}',
        TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS => '172.21.0.1',
    ];
}
