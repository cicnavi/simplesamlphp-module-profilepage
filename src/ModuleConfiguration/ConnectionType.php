<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\ModuleConfiguration;

final class ConnectionType
{
    public const MASTER = 'master';
    public const SLAVE = 'slave';

    public const VALID_OPTIONS = [
        self::MASTER,
        self::SLAVE,
    ];
}
