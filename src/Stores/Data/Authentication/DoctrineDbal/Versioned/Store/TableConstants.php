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
    public const TABLE_IDP_COLUMN_NAME_ID = 'id'; // sha256 hashed entity ID, hexits, char(64)
    public const TABLE_IDP_COLUMN_NAME_ENTITY_ID = 'entity_id'; // Entity ID value, string, varchar(1024)
    //public const TABLE_IDP_COLUMN_NAME_PAYLOAD = 'payload'; // Current IdP metadata can be fetched from metadata
    public const TABLE_IDP_COLUMN_NAME_CREATED_AT = 'created_at'; // First time IdP usage, datetime

    // Table 'idp_version'
    public const TABLE_NAME_IDP_VERSION = 'idp_version';
    // The ID is int despite the fact that the payload has unique hash. The reason is huge difference in storage
    // for int (4 bytes) vs char (64 chars/bytes) when using the ID in child table(s), primarily in table
    // 'authentication'.
    public const TABLE_IDP_VERSION_COLUMN_NAME_ID = 'id'; // int ID
    public const TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID = 'idp_id'; // FK, sha256 hashed IdP entity ID
    public const TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD = 'payload'; // Serialized IdP metadata version
    public const TABLE_IDP_VERSION_COLUMN_NAME_HASH_SHA256_PAYLOAD = 'payload_hash_sha256'; // Payload sha256 hash, unq

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
