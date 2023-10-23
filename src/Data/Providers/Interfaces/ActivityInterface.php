<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Providers\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface ActivityInterface extends DataProviderInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ): self;

    public function getActivity(string $userIdentifier, int $maxResults = null, int $firstResult = 0): Activity\Bag;
}
