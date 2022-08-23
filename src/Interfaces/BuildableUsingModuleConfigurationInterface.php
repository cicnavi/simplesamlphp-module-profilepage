<?php

namespace SimpleSAML\Module\accounting\Interfaces;

use SimpleSAML\Module\accounting\ModuleConfiguration;

interface BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration): self;
}
