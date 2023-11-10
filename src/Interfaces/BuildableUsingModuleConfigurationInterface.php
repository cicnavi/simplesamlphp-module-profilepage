<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

interface BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;
}
