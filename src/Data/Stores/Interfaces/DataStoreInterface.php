<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Interfaces;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

interface DataStoreInterface extends StoreInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ): self;

    public function persist(Event $authenticationEvent): void;

    public function deleteDataOlderThan(DateTimeImmutable $dateTime): void;
}
