<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Providers\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface AuthenticationDataProviderInterface extends BuildableUsingModuleConfigurationInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ): self;

    public function getConnectedServiceProviders(string $userIdentifier): ConnectedServiceProvider\Bag;

    public function getActivity(string $userIdentifier, int $maxResults, int $firstResult): Activity\Bag;
}
