<?php

namespace SimpleSAML\Module\accounting\ModuleConfiguration;

class AccountingProcessingType
{
    public const VALUE_SYNCHRONOUS = 'synchronous';
    public const VALUE_ASYNCHRONOUS = 'asynchronous';

    public const VALID_OPTIONS = [
        self::VALUE_SYNCHRONOUS,
        self::VALUE_ASYNCHRONOUS,
    ];
}
