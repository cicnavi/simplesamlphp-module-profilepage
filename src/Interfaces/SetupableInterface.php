<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Interfaces;

interface SetupableInterface
{
    public function needsSetup(): bool;
    public function runSetup(): void;
}
