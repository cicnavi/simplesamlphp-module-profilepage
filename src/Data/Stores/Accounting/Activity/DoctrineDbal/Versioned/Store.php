<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\RawActivity;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store as BaseStore;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\ActivityInterface;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use Throwable;

class Store extends BaseStore implements ActivityInterface
{
    protected Repository $repository;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        Factory $connectionFactory = null,
        HelpersManager $helpersManager = null,
        Repository $repository = null
    ) {
        parent::__construct(
            $moduleConfiguration,
            $logger,
            $connectionKey,
            $connectionType,
            $connectionFactory,
            $helpersManager,
            $repository
        );

        $this->repository = $repository ?? new Repository($this->connection, $this->logger);
    }

    /**
     * Build store instance.
     * @throws StoreException
     */
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER
    ): self {
        return new self(
            $moduleConfiguration,
            $logger,
            $connectionKey,
            $connectionType
        );
    }

    /**
     * @throws StoreException
     */
    public function persist(Event $authenticationEvent): void
    {
        $hashDecoratedState = new HashDecoratedState($authenticationEvent->getState());

        $idpId = $this->resolveIdpId($hashDecoratedState);
        $idpVersionId = $this->resolveIdpVersionId($idpId, $hashDecoratedState);
        $spId = $this->resolveSpId($hashDecoratedState);
        $spVersionId = $this->resolveSpVersionId($spId, $hashDecoratedState);
        $userId = $this->resolveUserId($hashDecoratedState);
        $userVersionId = $this->resolveUserVersionId($userId, $hashDecoratedState);
        $idpSpUserVersionId = $this->resolveIdpSpUserVersionId($idpVersionId, $spVersionId, $userVersionId);

        $this->repository->insertAuthenticationEvent(
            $idpSpUserVersionId,
            $authenticationEvent->getHappenedAt(),
            $authenticationEvent->getState()->getClientIpAddress(),
            $authenticationEvent->getState()->getAuthenticationProtocol()->getDesignation()
        );
    }

    /**
     * @throws StoreException
     */
    public function getActivity(string $userIdentifier, int $maxResults, int $firstResult): Activity\Bag
    {
        $userIdentifierHashSha256 = $this->helpersManager->getHash()->getSha256($userIdentifier);

        $results =  $this->repository->getActivity($userIdentifierHashSha256, $maxResults, $firstResult);

        $activityBag = new Activity\Bag();

        if (empty($results)) {
            return $activityBag;
        }

        try {
            /** @var array $result */
            foreach ($results as $result) {
                $rawActivity = new RawActivity($result, $this->connection->dbal()->getDatabasePlatform());
                $serviceProvider = $this->helpersManager
                    ->getProviderResolver()
                    ->forServiceFromMetadataArray($rawActivity->getServiceProviderMetadata());
                $user = new User($rawActivity->getUserAttributes());

                $activityBag->add(
                    new Activity(
                        $serviceProvider,
                        $user,
                        $rawActivity->getHappenedAt(),
                        $rawActivity->getClientIpAddress(),
                        $rawActivity->getAuthenticationProtocolDesignation()
                    )
                );
            }
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error populating activity bag. Error was: %s',
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $activityBag;
    }

    /**
     * @throws StoreException
     */
    public function deleteDataOlderThan(DateTimeImmutable $dateTime): void
    {
        // Only delete authentication events. VersionedDataProvider data (IdP / SP metadata, user attributes) remain.
        $this->repository->deleteAuthenticationEventsOlderThan($dateTime);
    }
}