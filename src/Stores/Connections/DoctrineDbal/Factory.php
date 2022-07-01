<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal;

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\LoggerService;

class Factory
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerService $loggerService;

    public function __construct(ModuleConfiguration $moduleConfiguration, LoggerService $loggerService)
    {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->loggerService = $loggerService;
    }

    public function buildConnection(string $connectionKey): Connection
    {
        return new Connection($this->moduleConfiguration->getStoreConnectionParameters($connectionKey));
    }

    public function buildMigrator(Connection $connection, LoggerService $loggerService = null): Migrator
    {
        return new Migrator($connection, $loggerService ?? $this->loggerService);
    }
}
