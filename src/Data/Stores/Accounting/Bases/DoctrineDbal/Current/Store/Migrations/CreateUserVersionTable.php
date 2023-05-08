<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations;

use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations;

class CreateUserVersionTable extends Migrations\CreateUserVersionTable
{
    protected function getLocalTablePrefix(): string
    {
        return 'cds_';
    }
}
