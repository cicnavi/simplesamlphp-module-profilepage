<?php

namespace SimpleSAML\Module\accounting\Services;

use SimpleSAML\Logger;

class LoggerService
{
    /**
     * Log an emergency message.
     *
     * @param string $message The message to log.
     */
    public function emergency(string $message): void
    {
        Logger::emergency($message);
    }

    /**
     * Log a critical message.
     *
     * @param string $message The message to log.
     */
    public function critical(string $message): void
    {
        Logger::critical($message);
    }

    /**
     * Log an alert message.
     *
     * @param string $message The message to log.
     */
    public function alert(string $message): void
    {
        Logger::alert($message);
    }

    /**
     * Log an error message.
     *
     * @param string $message The message to log.
     */
    public function error(string $message): void
    {
        Logger::error($message);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The message to log.
     */
    public function warning(string $message): void
    {
        Logger::warning($message);
    }

    /**
     * Log a notice message.
     *
     * @param string $message The message to log.
     */
    public function notice(string $message): void
    {
        Logger::notice($message);
    }

    /**
     * Log an info message (a bit less verbose than debug messages).
     *
     * @param string $message The message to log.
     */
    public function info(string $message): void
    {
        Logger::info($message);
    }

    /**
     * Log a debug message (very verbose messages).
     *
     * @param string $message The message to log.
     */
    public function debug(string $message): void
    {
        Logger::debug($message);
    }

    /**
     * Log a statistics message.
     *
     * @param string $message The message to log.
     */
    public function stats(string $message): void
    {
        Logger::stats($message);
    }
}
