<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

class TableConstants
{
    public const TABLE_PREFIX = 'vds_'; // versioned data store

    // Any SAML entity ID should have maximum 1024 chars per
    // https://stackoverflow.com/questions/24196369/what-to-present-at-saml-entityid-url
    public const COLUMN_ENTITY_ID_LENGTH = 1024;
    public const COLUMN_HASH_SHA265_HEXITS_LENGTH = 64;


    // Table 'idp'
    public const TABLE_NAME_IDP = 'idp';
    public const TABLE_IDP_COLUMN_NAME_ID = 'id'; // int
    public const TABLE_IDP_COLUMN_NAME_ENTITY_ID = 'entity_id'; // Entity ID value, string, varchar(1024)
    public const TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 = 'entity_id_hash_sha256'; // ha256 hash hexits, char(64)
    public const TABLE_IDP_COLUMN_NAME_CREATED_AT = 'created_at'; // First time IdP usage, datetime

    // Table 'idp_version'
    public const TABLE_NAME_IDP_VERSION = 'idp_version';
    public const TABLE_IDP_VERSION_COLUMN_NAME_ID = 'id'; // int ID
    public const TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID = 'idp_id'; // FK
    public const TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD = 'payload'; // Serialized IdP metadata version
    public const TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 = 'payload_hash_sha256'; // Payload sha256 hash, unq
    public const TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT = 'created_at';

    // Table 'sp', same structure as in 'idp'
    public const TABLE_NAME_SP = 'sp';
    public const TABLE_SP_COLUMN_NAME_ID = 'id';
    public const TABLE_SP_COLUMN_NAME_ENTITY_ID = 'entity_id';
    public const TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 = 'entity_id_hash_sha256';
    public const TABLE_SP_COLUMN_NAME_CREATED_AT = 'created_at';

    // Table 'sp_version', same structure as in 'idp_version'
    public const TABLE_NAME_SP_VERSION = 'sp_version';
    public const TABLE_SP_VERSION_COLUMN_NAME_ID = 'id';
    public const TABLE_SP_VERSION_COLUMN_NAME_SP_ID = 'sp_id';
    public const TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD = 'payload';
    public const TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 = 'payload_hash_sha256';
    public const TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT = 'created_at';

    // Table 'user'
    public const TABLE_NAME_USER = 'user';
    public const TABLE_USER_COLUMN_NAME_ID = 'id'; // int
    public const TABLE_USER_COLUMN_NAME_IDENTIFIER = 'identifier'; // text, varies... (can be ePTID, which is long XML).
    public const TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 = 'identifier_hash_sha256';
    public const TABLE_USER_COLUMN_NAME_CREATED_AT = 'created_at';

    public const TABLE_NAME_USER_VERSION = 'user_version';
    public const TABLE_USER_VERSION_COLUMN_NAME_ID = 'id'; // int ID
    public const TABLE_USER_VERSION_COLUMN_NAME_USER_ID = 'user_id'; // FK
    public const TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD = 'payload'; // Serialized attributes metadata version
    public const TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 = 'payload_hash_sha256'; // Payload sha256 hash, unq
    public const TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT = 'created_at';

    // Attribute versions released to SP version
    public const TABLE_NAME_SP_VERSION_USER_VERSION = 'sp_version_user_version';

    // Attribute set history, contains information per IdP / SP / user on every attribute
    // that was ever released to SP, including the latest value and release date
    public const TABLE_NAME_IDP_SP_USER_ATTRIBUTE_SET_HISTORY = 'idp_sp_user_attribute_set_history';

    public const TABLE_NAME_AUTHENTICATION = 'authentication';
}
