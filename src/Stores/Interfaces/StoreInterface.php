<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

interface StoreInterface
{
    public function needsSetUp(): bool;
}
