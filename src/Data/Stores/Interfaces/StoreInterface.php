<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\profilepage\Interfaces\SetupableInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

interface StoreInterface extends BuildableUsingModuleConfigurationInterface, SetupableInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self;
}
