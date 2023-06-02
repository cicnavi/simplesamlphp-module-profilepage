<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;
}
