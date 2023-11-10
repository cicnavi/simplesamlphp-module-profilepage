<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

class Environment
{
    public function isCli(): bool
    {
        return http_response_code() === false;
    }
}
