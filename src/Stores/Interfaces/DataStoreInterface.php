<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface DataStoreInterface extends StoreInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self;
}
