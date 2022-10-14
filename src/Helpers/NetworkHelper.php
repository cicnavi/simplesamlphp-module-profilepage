<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

// TODO mivanci move to HelpersManager
class NetworkHelper
{
    public static function resolveClientIpAddress(string $clientIpAddress = null): ?string
    {
        /** @var string|null $clientIpAddress */
        $clientIpAddress = $clientIpAddress ??
            $_SERVER['HTTP_CLIENT_IP'] ??
            $_SERVER['HTTP_X_FORWARDED_FOR'] ??
            $_SERVER['REMOTE_ADDR'] ??
            null;

        if (!is_string($clientIpAddress)) {
            return null;
        }

        $ips = explode(',', $clientIpAddress);

        $ip = mb_substr(trim(array_pop($ips)), 0, 45);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return null;
    }
}
