<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

use SimpleSAML\Module\accounting\ModuleConfiguration;

class Factory
{
    protected ModuleConfiguration $moduleConfiguration;

    public function __construct(ModuleConfiguration $moduleConfiguration)
    {
        $this->moduleConfiguration = $moduleConfiguration;
    }

    public function buildConnection(string $connectionKey): Connection
    {
        return new Connection($this->moduleConfiguration->getStoreConnectionParameters($connectionKey));
    }

    public function buildMigrator(Connection $connection): Migrator
    {
        return new Migrator($connection);
    }
}
