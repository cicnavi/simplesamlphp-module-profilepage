<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

class TableConstants
{
    public const TABLE_PREFIX = 'vds_'; // versioned data store

    public const TABLE_NAME_IDP = 'idp';
    public const TABLE_IDP_COLUMN_NAME_ID = 'id'; // int ID
    public const TABLE_IDP_COLUMN_NAME_ENTITY_ID = 'entity_id'; // Entity ID, unique string
    // Per https://stackoverflow.com/questions/24196369/what-to-present-at-saml-entityid-url
    public const TABLE_IDP_COLUMN_ENTITY_ID_LENGTH = 1024;
    //public const TABLE_IDP_COLUMN_NAME_PAYLOAD = 'payload'; // Current IdP metadata can be fetched from metadata
    public const TABLE_IDP_COLUMN_NAME_CREATED_AT = 'created_at'; // First time IdP usage

    public const TABLE_NAME_IDP_VERSION = 'idp_version';
    public const TABLE_IDP_VERSION_COLUMN_NAME_ID = 'id'; // int ID
    public const TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID = 'idp_id'; // int idp_id
    public const TABLE_IDP_VERSION_COLUMN_NAME_HASH = 'hash'; // Payload hash
    public const TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD = 'payload'; // Payload

    public const TABLE_NAME_SP = 'sp';
    public const TABLE_NAME_SP_VERSION = 'sp_version';

    public const TABLE_NAME_USER = 'user';

    public const TABLE_NAME_USER_ATTRIBUTE_VERSION = 'user_attribute_version';

    // Attribute versions released to SP
    public const TABLE_NAME_SP_USER_ATTRIBUTE_VERSION = 'sp_user_attribute_version';

    // Attribute set history, contains information per IdP / SP / user on every attribute
    // that was ever released to SP, including the latest value and release date
    public const TABLE_NAME_IDP_SP_USER_ATTRIBUTE_SET_HISTORY = 'idp_sp_user_attribute_set_history';

    public const TABLE_NAME_AUTHENTICATION = 'authentication';
}
