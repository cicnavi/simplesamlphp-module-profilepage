<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\Factories;

use SimpleSAML\Module\accounting\Exceptions\ModuleConfiguration\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\PdoConnection;

class PdoConnectionFactory
{
    protected ModuleConfiguration $moduleConfiguration;

    public function __construct(ModuleConfiguration $moduleConfiguration)
    {
        $this->moduleConfiguration = $moduleConfiguration;
    }

    public function build(string $connection): PdoConnection
    {
        $settings = $this->moduleConfiguration->getConnectionSettins($connection);

        $this->validateSettings($settings, $connection);

        return new PdoConnection(
            $settings[PdoConnection::OPTION_DSN],
            $settings[PdoConnection::OPTION_USERNAME] ?? null,
            $settings[PdoConnection::OPTION_PASSWORD] ?? null,
            $settings[PdoConnection::OPTION_DRIVER_OPTIONS] ?? null,
        );
    }

    protected function validateSettings(?array $settings, string $connection)
    {
        if (! is_array($settings)) {
            throw new InvalidConfigurationException(
                sprintf('Connection settings for %s not set in module configuration.', $connection)
            );
        }

        if (! isset($settings[PdoConnection::OPTION_DSN]) || ! (is_string($settings[PdoConnection::OPTION_DSN]))) {
            throw new InvalidConfigurationException(
                sprintf('Connection DSN not set for connection %s', $connection)
            );
        }
    }
}
