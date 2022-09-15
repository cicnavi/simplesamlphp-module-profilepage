<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Providers\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider\Bag;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface AuthenticationDataProviderInterface extends BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;

    // TODO mivanci replace Result with proper "bag"
    public function getConnectedServiceProviders(string $userIdentifier): Bag;

    // TODO mivanci replace Result with proper "bag"
    public function getActivity(string $userIdentifier): array;
}
