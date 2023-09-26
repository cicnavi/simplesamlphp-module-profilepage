<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use SimpleSAML\Logger as SspLogger;

class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = [], string $prefix = '(accounting) '): void
    {
        $message = $prefix . $message;

        if (! empty($context)) {
            $message .= ' Context: ' . var_export($context, true);
        }

        switch ($level) {
            case LogLevel::EMERGENCY:
                SspLogger::emergency($message);
                break;
            case LogLevel::CRITICAL:
                SspLogger::critical($message);
                break;
            case LogLevel::ALERT:
                SspLogger::alert($message);
                break;
            case LogLevel::ERROR:
                SspLogger::error($message);
                break;
            case LogLevel::WARNING:
                SspLogger::warning($message);
                break;
            case LogLevel::NOTICE:
                SspLogger::notice($message);
                break;
            case LogLevel::INFO:
                SspLogger::info($message);
                break;
            case LogLevel::DEBUG:
                SspLogger::debug($message);
                break;
        }
    }

    /**
     * Log an SSP statistics message.
     *
     * @param string $message The message to log.
     */
    public function stats(string $message): void
    {
        SspLogger::stats($message);
    }
}
