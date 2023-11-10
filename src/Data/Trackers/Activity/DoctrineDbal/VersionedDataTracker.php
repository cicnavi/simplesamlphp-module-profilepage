<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Trackers\Activity\DoctrineDbal;

use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider;
use SimpleSAML\Module\profilepage\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

class VersionedDataTracker extends VersionedDataProvider implements DataTrackerInterface
{
    /**
     * @throws StoreException
     */
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ): self {
        return new self($moduleConfiguration, $logger, $connectionType);
    }

    /**
     * @throws StoreException
     */
    public function process(Event $authenticationEvent): void
    {
        $this->store->persist($authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function enforceDataRetentionPolicy(DateInterval $retentionPolicy): void
    {
        $dateTime = (new DateTimeImmutable())->sub($retentionPolicy);

        $this->store->deleteDataOlderThan($dateTime);
    }
}
