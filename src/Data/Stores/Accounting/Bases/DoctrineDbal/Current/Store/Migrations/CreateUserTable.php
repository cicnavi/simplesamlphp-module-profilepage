<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations;

use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations;

/**
 * We use versioned data to manage users, so we reuse versioned user table definitions.
 */
class CreateUserTable extends Migrations\CreateUserTable
{
}
