<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Trackers\Interfaces;

use DateInterval;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\profilepage\Interfaces\SetupableInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

interface DataTrackerInterface extends BuildableUsingModuleConfigurationInterface, SetupableInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;

    public function process(Event $authenticationEvent): void;

    public function enforceDataRetentionPolicy(DateInterval $retentionPolicy): void;
}
