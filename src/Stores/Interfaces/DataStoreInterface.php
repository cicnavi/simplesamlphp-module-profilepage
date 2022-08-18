<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use SimpleSAML\Module\accounting\ModuleConfiguration;

interface DataStoreInterface extends StoreInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration): self;
}
