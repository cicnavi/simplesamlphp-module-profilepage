<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Connections\Pdo\Factories;

use SimpleSAML\Module\accounting\Exceptions\ModuleConfiguration\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\PdoConnection;

class PdoConnectionFactory
{
    protected ModuleConfiguration $moduleConfiguration;

    public function __construct(ModuleConfiguration $moduleConfiguration)
    {
        $this->moduleConfiguration = $moduleConfiguration;
    }

    public function build(string $connection): PdoConnection
    {
        $settings = $this->moduleConfiguration->getStoreConnectionParameters($connection);

        $dsn = $this->getDsnFromSettings($settings);
        $username = $this->getUsernameFromSettings($settings);
        $password = $this->getPasswordFromSettings($settings);
        $driverOptions = $this->getDriverOptionsFromSettings($settings);
        $tablePrefix = $this->getTablePrefixFromSettings($settings);

        return new PdoConnection(
            $dsn,
            $username,
            $password,
            $driverOptions,
            $tablePrefix
        );
    }

    protected function getDsnFromSettings(array $settings): string
    {
        if (
            ! isset($settings[PdoConnection::OPTION_DSN]) ||
            ! (is_string($settings[PdoConnection::OPTION_DSN])) ||
            empty($settings[PdoConnection::OPTION_DSN])
        ) {
            throw new InvalidConfigurationException('PDO connection DSN setting not set.');
        }

        return $settings[PdoConnection::OPTION_DSN];
    }

    protected function getUsernameFromSettings(array $settings): ?string
    {
        if (! isset($settings[PdoConnection::OPTION_USERNAME])) {
            return null;
        }

        if (! is_string($settings[PdoConnection::OPTION_USERNAME])) {
            throw new InvalidConfigurationException('PDO connection username must be string (if set).');
        }

        if (empty($settings[PdoConnection::OPTION_USERNAME])) {
            throw new InvalidConfigurationException('PDO connection username can not be empty string (if set).');
        }

        return $settings[PdoConnection::OPTION_USERNAME];
    }

    protected function getPasswordFromSettings(array $settings): ?string
    {
        if (! isset($settings[PdoConnection::OPTION_PASSWORD])) {
            return null;
        }

        if (! is_string($settings[PdoConnection::OPTION_PASSWORD])) {
            throw new InvalidConfigurationException('PDO connection password must be string (if set).');
        }

        if (empty($settings[PdoConnection::OPTION_PASSWORD])) {
            throw new InvalidConfigurationException('PDO connection password can not be empty string (if set).');
        }

        return $settings[PdoConnection::OPTION_PASSWORD];
    }

    protected function getDriverOptionsFromSettings(array $settings): ?array
    {
        if (! isset($settings[PdoConnection::OPTION_DRIVER_OPTIONS])) {
            return null;
        }

        if (! is_array($settings[PdoConnection::OPTION_DRIVER_OPTIONS])) {
            throw new InvalidConfigurationException('PDO connection driver options must be array (if set).');
        }

        return $settings[PdoConnection::OPTION_DRIVER_OPTIONS];
    }

    protected function getTablePrefixFromSettings(array $settings): ?string
    {
        if (! isset($settings[PdoConnection::OPTION_TABLE_PREFIX])) {
            return null;
        }

        if (! is_string($settings[PdoConnection::OPTION_TABLE_PREFIX])) {
            throw new InvalidConfigurationException('PDO connection table prefix must be string (if set).');
        }

        return $settings[PdoConnection::OPTION_TABLE_PREFIX];
    }
}
