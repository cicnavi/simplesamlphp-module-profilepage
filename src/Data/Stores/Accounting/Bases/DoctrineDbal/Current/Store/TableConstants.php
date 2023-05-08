<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store;

use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\TableConstants as BaseTableConstants;

class TableConstants extends BaseTableConstants
{
    public const TABLE_PREFIX = 'cds_'; // current data store

    // Table 'idp'
    public const TABLE_NAME_IDP = 'idp';
    public const TABLE_ALIAS_IDP = self::TABLE_PREFIX . 'i';
    public const TABLE_IDP_COLUMN_NAME_ID = 'id'; // int
    public const TABLE_IDP_COLUMN_NAME_ENTITY_ID = 'entity_id'; // Entity ID value, string, varchar(1024)
    public const TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 = 'entity_id_hash_sha256'; // ha256 hash hexits, char(64)
    public const TABLE_IDP_COLUMN_NAME_METADATA = 'metadata'; // Serialized IdP metadata version
    public const TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256 = 'metadata_hash_sha256'; // Metadata sha256 hash
    public const TABLE_IDP_COLUMN_NAME_CREATED_AT = 'created_at'; // First time IdP usage, datetime
    public const TABLE_IDP_COLUMN_NAME_UPDATED_AT = 'updated_at'; // First time IdP usage, datetime

    // Table 'sp', same structure as in 'idp'
    public const TABLE_NAME_SP = 'sp';
    public const TABLE_ALIAS_SP = self::TABLE_PREFIX . 's';
    public const TABLE_SP_COLUMN_NAME_ID = 'id';
    public const TABLE_SP_COLUMN_NAME_ENTITY_ID = 'entity_id';
    public const TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 = 'entity_id_hash_sha256';
    public const TABLE_SP_COLUMN_NAME_METADATA = 'metadata';
    public const TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256 = 'metadata_hash_sha256';
    public const TABLE_SP_COLUMN_NAME_CREATED_AT = 'created_at';
    public const TABLE_SP_COLUMN_NAME_UPDATED_AT = 'updated_at';

    // Table 'user'
    public const TABLE_NAME_USER = 'user';
    public const TABLE_ALIAS_USER = self::TABLE_PREFIX . 'u';
    public const TABLE_USER_COLUMN_NAME_ID = 'id'; // int
    public const TABLE_USER_COLUMN_NAME_IDENTIFIER = 'identifier'; // text, varies... (can be ePTID, which is long XML).
    public const TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 = 'identifier_hash_sha256';
    public const TABLE_USER_COLUMN_NAME_CREATED_AT = 'created_at';

    // Table 'user_version' (versioned attributes)
    public const TABLE_NAME_USER_VERSION = 'user_version';
    public const TABLE_ALIAS_USER_VERSION = self::TABLE_PREFIX . 'uv';
    public const TABLE_ALIAS_USER_VERSION_2 = self::TABLE_ALIAS_USER_VERSION . '_2';
    public const TABLE_USER_VERSION_COLUMN_NAME_ID = 'id'; // int ID
    public const TABLE_USER_VERSION_COLUMN_NAME_USER_ID = 'user_id'; // FK
    public const TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES = 'attributes'; // Serialized attributes version
    public const TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256 = 'attributes_hash_sha256';
    public const TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT = 'created_at';
}