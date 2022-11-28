<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

class EnvironmentHelper
{
    public function isCli(): bool
    {
        return http_response_code() === false;
    }
}
