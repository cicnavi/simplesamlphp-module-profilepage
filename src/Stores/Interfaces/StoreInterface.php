<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use SimpleSAML\Module\accounting\ModuleConfiguration;

interface StoreInterface
{
    public function needsSetUp(): bool;
    public function runSetup(): void;

    public static function build(ModuleConfiguration $moduleConfiguration): self;
}
