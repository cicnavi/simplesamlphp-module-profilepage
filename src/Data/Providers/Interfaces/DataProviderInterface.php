<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Providers\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\profilepage\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\profilepage\Interfaces\SetupableInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

interface DataProviderInterface extends BuildableUsingModuleConfigurationInterface, SetupableInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE
    ): self;

    public function getTracker(): ?DataTrackerInterface;
}
