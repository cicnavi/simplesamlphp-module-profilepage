<?php

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

interface StoreInterface
{
    public function needsSetUp(): bool;
}
