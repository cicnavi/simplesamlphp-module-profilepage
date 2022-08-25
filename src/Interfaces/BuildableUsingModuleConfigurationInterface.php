<?php

namespace SimpleSAML\Module\accounting\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface BuildableUsingModuleConfigurationInterface
{
    public const BUILD_METHOD = 'build';

    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;
}
