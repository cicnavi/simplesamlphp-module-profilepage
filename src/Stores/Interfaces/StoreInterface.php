<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface StoreInterface extends BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration): self;

    public function needsSetup(): bool;
    public function runSetup(): void;
}
