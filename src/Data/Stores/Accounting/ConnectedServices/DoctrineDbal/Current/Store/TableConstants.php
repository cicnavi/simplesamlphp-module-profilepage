<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;

use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants
    as BaseTableConstants;

class TableConstants
{
    // Table 'connected_service' (connected organizations).
    public const TABLE_NAME_CONNECTED_SERVICE = 'connected_service';
    public const TABLE_ALIAS_CONNECTED_SERVICE = BaseTableConstants::TABLE_PREFIX . 'cs';
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_ID = 'id'; // int
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID = 'sp_id'; // int
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID = 'user_id'; // int
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID = 'user_version_id'; // int
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT = 'first_authentication_at'; // datetime
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT = 'last_authentication_at'; // datetime
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT = 'count'; // int
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_CREATED_AT = 'created_at'; // datetime
    public const TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT = 'updated_at'; // datetime
}
