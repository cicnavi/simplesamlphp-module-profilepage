<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned;

use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Stores\Builders\DataStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Stores\Interfaces\DataStoreInterface;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;

class Tracker implements AuthenticationDataTrackerInterface, AuthenticationDataProviderInterface
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected DataStoreInterface $dataStore;
    protected HelpersManager $helpersManager;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        HelpersManager $helpersManager = null,
        DataStoreInterface $dataStore = null
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;

        $this->helpersManager = $helpersManager ?? new HelpersManager();

        // Use provided store or initialize default store for this tracker.
        $this->dataStore = $dataStore ??
            (new DataStoreBuilder($this->moduleConfiguration, $this->logger, $this->helpersManager))
                ->build(
                    Store::class,
                    $this->moduleConfiguration->getClassConnectionKey(self::class),
                    $connectionType
                );
    }

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
        if (! $this->needsSetup()) {
            $this->logger->warning('Run setup called, however setup is not needed.');
            return;
        }

        $this->dataStore->runSetup();
    }

    public function getConnectedServiceProviders(string $userIdentifier): ConnectedServiceProvider\Bag
    {
        $userIdentifierHashSha256 = $this->helpersManager->getHashHelper()->getSha256($userIdentifier);
        return $this->dataStore->getConnectedOrganizations($userIdentifierHashSha256);
    }

    public function getActivity(string $userIdentifier, int $maxResults, int $firstResult): Activity\Bag
    {
        $userIdentifierHashSha256 = $this->helpersManager->getHashHelper()->getSha256($userIdentifier);
        return $this->dataStore->getActivity($userIdentifierHashSha256, $maxResults, $firstResult);
    }

    public function enforceDataRetentionPolicy(DateInterval $retentionPolicy): void
    {
        $dateTime = (new DateTimeImmutable())->sub($retentionPolicy);

        if ($dateTime === false) {
            // @codeCoverageIgnoreStart
            $message = sprintf(
                'Could not create DateTime instance for data retention policy enforcement. Retention policy was: %s.',
                var_export($retentionPolicy, true)
            );
            $this->logger->error($message);
            throw new InvalidConfigurationException($message);
            // @codeCoverageIgnoreEnd
        }

        $this->dataStore->deleteDataOlderThan($dateTime);
    }
}
