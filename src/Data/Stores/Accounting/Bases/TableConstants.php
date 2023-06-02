<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases;

class TableConstants
{
    // Any SAML entity ID should have maximum 1024 chars per
    // https://stackoverflow.com/questions/24196369/what-to-present-at-saml-entityid-url
    public const COLUMN_ENTITY_ID_LENGTH = 1024;
    public const COLUMN_HASH_SHA265_HEXITS_LENGTH = 64;
    public const COLUMN_IP_ADDRESS_LENGTH = 45;
    public const COLUMN_AUTHENTICATION_PROTOCOL_DESIGNATION_LENGTH = 16;
}
