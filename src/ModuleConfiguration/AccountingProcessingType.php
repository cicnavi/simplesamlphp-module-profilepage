<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\ModuleConfiguration;

final class AccountingProcessingType
{
    public const VALUE_SYNCHRONOUS = 'synchronous';
    public const VALUE_ASYNCHRONOUS = 'asynchronous';

    public const VALID_OPTIONS = [
        self::VALUE_SYNCHRONOUS,
        self::VALUE_ASYNCHRONOUS,
    ];
}
