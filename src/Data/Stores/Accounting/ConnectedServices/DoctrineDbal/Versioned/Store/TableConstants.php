<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;

use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants
    as BaseTableConstants;

class TableConstants
{
    // Table 'connected_service' (connected organizations).
    final public const TABLE_NAME_CONNECTED_SERVICE = 'connected_service';
    final public const TABLE_ALIAS_CONNECTED_SERVICE = BaseTableConstants::TABLE_PREFIX . 'cs';
    final public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_ID = 'id'; // int
    final public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID = 'idp_sp_user_version_id'; // int
    // datetime
    final public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT = 'first_authentication_at';
    // datetime
    final public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT = 'last_authentication_at';
    final public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT = 'count'; // int
    final public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_CREATED_AT = 'created_at'; // datetime
    final public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT = 'updated_at'; // datetime
}
