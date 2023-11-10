<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Providers\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Entities\ConnectedService;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

interface ConnectedServicesInterface extends DataProviderInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE
    ): self;

    public function getConnectedServices(string $userIdentifier): ConnectedService\Bag;
}
