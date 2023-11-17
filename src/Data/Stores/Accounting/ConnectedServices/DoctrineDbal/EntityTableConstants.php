<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal;

class EntityTableConstants
{
    // Entity 'ConnectedService' (service provider) related.
    final public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_ENTITY_ID = 'sp_entity_id';
    final public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS = 'number_of_authentications';
    final public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT = 'last_authentication_at';
    final public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT = 'first_authentication_at';
    final public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA = 'sp_metadata';
    final public const ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES = 'user_attributes';
}
