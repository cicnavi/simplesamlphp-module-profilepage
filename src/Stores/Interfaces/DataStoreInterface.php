<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Interfaces;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\ModuleConfiguration;

interface DataStoreInterface extends StoreInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ): self;

    public function persist(Event $authenticationEvent): void;

    public function getConnectedOrganizations(string $userIdentifierHashSha256): ConnectedServiceProvider\Bag;

    public function getActivity(string $userIdentifierHashSha256, int $maxResults, int $firstResult): Activity\Bag;
}
