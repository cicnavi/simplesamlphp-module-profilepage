<?php

namespace SimpleSAML\Module\accounting\Trackers\Interfaces;

use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;

interface TrackerInterface extends BuildableUsingModuleConfigurationInterface, AuthenticationDataProviderInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration): self;

    public function process(Event $authenticationEvent): void;
}
