<?php

namespace SimpleSAML\Module\accounting\Providers\Interfaces;

use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface AuthenticationDataProviderInterface extends BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration): self;
}
