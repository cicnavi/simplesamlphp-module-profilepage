<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Trackers\Interfaces;

use DateInterval;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\Interfaces\SetupableInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface DataTrackerInterface extends BuildableUsingModuleConfigurationInterface, SetupableInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;

    public function process(Event $authenticationEvent): void;

    public function enforceDataRetentionPolicy(DateInterval $retentionPolicy): void;
}
