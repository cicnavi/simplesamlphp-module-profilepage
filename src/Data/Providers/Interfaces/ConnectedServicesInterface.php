<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Providers\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\ConnectedService;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface ConnectedServicesInterface extends DataProviderInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::SLAVE
    ): self;

    public function getConnectedServices(string $userIdentifier): ConnectedService\Bag;
}
