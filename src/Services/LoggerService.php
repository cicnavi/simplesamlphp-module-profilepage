<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use SimpleSAML\Logger;

class LoggerService extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        if (! empty($context)) {
            $message .= ' Context: ' . var_export($context, true);
        }

        switch ($level) {
            case LogLevel::EMERGENCY:
                Logger::emergency($message);
                break;
            case LogLevel::CRITICAL:
                Logger::critical($message);
                break;
            case LogLevel::ALERT:
                Logger::alert($message);
                break;
            case LogLevel::ERROR:
                Logger::error($message);
                break;
            case LogLevel::WARNING:
                Logger::warning($message);
                break;
            case LogLevel::NOTICE:
                Logger::notice($message);
                break;
            case LogLevel::INFO:
                Logger::info($message);
                break;
            case LogLevel::DEBUG:
                Logger::debug($message);
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
        Logger::stats($message);
    }
}
