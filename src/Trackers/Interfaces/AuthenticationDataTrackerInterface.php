<?php

namespace SimpleSAML\Module\accounting\Trackers\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;

interface AuthenticationDataTrackerInterface extends BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;

    public function process(Event $authenticationEvent): void;
}
