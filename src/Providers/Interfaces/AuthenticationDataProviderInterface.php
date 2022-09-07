<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Providers\Interfaces;

use Doctrine\DBAL\Result;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface AuthenticationDataProviderInterface extends BuildableUsingModuleConfigurationInterface
{
    public static function build(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger): self;

    // TODO mivanci replace Result with proper "bag"
    public function getConnectedOrganizations(string $userIdentifier): array;

    // TODO mivanci replace Result with proper "bag"
    public function getActivity(string $userIdentifier): array;
}
