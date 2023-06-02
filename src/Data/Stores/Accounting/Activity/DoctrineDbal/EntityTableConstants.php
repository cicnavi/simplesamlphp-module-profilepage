<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal;

class EntityTableConstants
{
    // Entity 'Activity' related.
    public const ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA = 'sp_metadata';
    public const ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES = 'user_attributes';
    public const ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT = 'happened_at';
    public const ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS = 'client_ip_address';
    public const ENTITY_ACTIVITY_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =
        'authentication_protocol_designation';
}
