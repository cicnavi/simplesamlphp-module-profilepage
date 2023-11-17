<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store;

use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\TableConstants as BaseTableConstants;

class TableConstants extends BaseTableConstants
{
    final public const TABLE_PREFIX = 'cds_'; // current data store

    // Table 'idp'
    final public const TABLE_NAME_IDP = 'idp';
    final public const TABLE_ALIAS_IDP = self::TABLE_PREFIX . 'i';
    final public const TABLE_IDP_COLUMN_NAME_ID = 'id'; // int
    final public const TABLE_IDP_COLUMN_NAME_ENTITY_ID = 'entity_id'; // Entity ID value, string, varchar(1024)
    // ha256 hash hexits, char(64)
    final public const TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 = 'entity_id_hash_sha256';
    final public const TABLE_IDP_COLUMN_NAME_METADATA = 'metadata'; // Serialized IdP metadata version
    final public const TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256 = 'metadata_hash_sha256'; // Metadata sha256 hash
    final public const TABLE_IDP_COLUMN_NAME_CREATED_AT = 'created_at'; // First time IdP usage, datetime
    final public const TABLE_IDP_COLUMN_NAME_UPDATED_AT = 'updated_at'; // First time IdP usage, datetime

    // Table 'sp', same structure as in 'idp'
    final public const TABLE_NAME_SP = 'sp';
    final public const TABLE_ALIAS_SP = self::TABLE_PREFIX . 's';
    final public const TABLE_SP_COLUMN_NAME_ID = 'id';
    final public const TABLE_SP_COLUMN_NAME_ENTITY_ID = 'entity_id';
    final public const TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 = 'entity_id_hash_sha256';
    final public const TABLE_SP_COLUMN_NAME_METADATA = 'metadata';
    final public const TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256 = 'metadata_hash_sha256';
    final public const TABLE_SP_COLUMN_NAME_CREATED_AT = 'created_at';
    final public const TABLE_SP_COLUMN_NAME_UPDATED_AT = 'updated_at';
}
