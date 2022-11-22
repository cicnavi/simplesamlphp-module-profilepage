<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;

class Factory
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $loggerService;

    public function __construct(ModuleConfiguration $moduleConfiguration, LoggerInterface $loggerService)
    {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->loggerService = $loggerService;
    }

    public function buildConnection(string $connectionKey): Connection
    {
        return new Connection($this->moduleConfiguration->getConnectionParameters($connectionKey));
    }

    /**
     * @throws StoreException
     */
    public function buildMigrator(Connection $connection): Migrator
    {
        return new Migrator($connection, $this->loggerService);
    }
}
