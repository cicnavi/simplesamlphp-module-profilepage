<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned;

use Doctrine\DBAL\Result;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Helpers\HashHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;
use SimpleSAML\Module\accounting\Stores\Builders\DataStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Stores\Interfaces\DataStoreInterface;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;

class Tracker implements AuthenticationDataTrackerInterface, AuthenticationDataProviderInterface
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected DataStoreInterface $dataStore;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        DataStoreInterface $dataStore = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;

        // Use provided store or initialize default store for this tracker.
        $this->dataStore = $dataStore ??
            (new DataStoreBuilder($this->moduleConfiguration, $this->logger))
                ->build(Store::class, $this->moduleConfiguration->getClassConnectionKey(self::class));
    }

    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger
    ): self {
        return new self($moduleConfiguration, $logger);
    }

    public function process(Event $authenticationEvent): void
    {
        $this->dataStore->persist($authenticationEvent);
    }

    public function needsSetup(): bool
    {
        return $this->dataStore->needsSetup();
    }

    public function runSetup(): void
    {
        $this->dataStore->runSetup();
    }

    public function getConnectedOrganizations(string $userIdentifier): Result
    {
        // TODO mivanci refactor all this...
        $userIdentifierHashSha256 = HashHelper::getSha256($userIdentifier);
        return $this->dataStore->getConnectedOrganizations($userIdentifierHashSha256);
    }

    public function getActivity(int $userId): Result
    {
        // TODO: Implement getActivity() method.
    }
}
