<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal;

class EntityTableConstants
{
    // Entity 'ConnectedService' (service provider) related.
    public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_ENTITY_ID = 'sp_entity_id';
    public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS = 'number_of_authentications';
    public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT = 'last_authentication_at';
    public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT = 'first_authentication_at';
    public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA = 'sp_metadata';
    public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES = 'user_attributes';
}
