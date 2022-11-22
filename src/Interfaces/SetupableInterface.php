<?php

namespace SimpleSAML\Module\accounting\Interfaces;

interface SetupableInterface
{
    public function needsSetup(): bool;
    public function runSetup(): void;
}
