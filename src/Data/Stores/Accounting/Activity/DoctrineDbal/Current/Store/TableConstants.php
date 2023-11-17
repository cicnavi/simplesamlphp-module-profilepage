<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store;

// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants as BaseTableConstants;

class TableConstants
{
    // Table 'authentication_event'.
    final public const TABLE_NAME_AUTHENTICATION_EVENT = 'authentication_event';
    final public const TABLE_ALIAS_AUTHENTICATION_EVENT = BaseTableConstants::TABLE_PREFIX . 'ae';
    final public const TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_ID = 'id';
    final public const TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_SP_ID = 'sp_id';
    final public const TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_USER_VERSION_ID = 'user_version_id';
    final public const TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT = 'happened_at';
    final public const TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS = 'client_ip_address';
    final public const TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =
        'authentication_protocol_designation';
    final public const TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT = 'created_at';
}
