<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store as BaseStore;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\RawConnectedService;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\ConnectedServicesInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\ConnectedService;
use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use Throwable;

class Store extends BaseStore implements ConnectedServicesInterface
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
            $helpersManager
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

        /** @psalm-suppress MixedAssignment */
        if (
            ($connectedServiceId = $this->repository->getConnectedService($idpSpUserVersionId)->fetchOne()) !== false
        ) {
            $this->repository->updateConnectedServiceVersionCount(
                (int)$connectedServiceId,
                $authenticationEvent->getHappenedAt()
            );
        } else {
            $this->repository->insertConnectedService($idpSpUserVersionId, $authenticationEvent->getHappenedAt());
        }

        $this->repository->touchConnectedServiceVersionsTimestamp($userId, $spId);
    }

    /**
     * @throws StoreException
     */
    public function getConnectedServices(string $userIdentifier): ConnectedService\Bag
    {
        $connectedServiceProviderBag = new ConnectedService\Bag();

        $userIdentifierHashSha256 = $this->helpersManager->getHash()->getSha256($userIdentifier);

        $results = $this->repository->getConnectedServices($userIdentifierHashSha256);

        if (empty($results)) {
            return $connectedServiceProviderBag;
        }

        try {
            $databasePlatform = $this->connection->dbal()->getDatabasePlatform();

            /** @var array $result */
            foreach ($results as $result) {
                $rawConnectedServiceProvider = new RawConnectedService($result, $databasePlatform);

                $serviceProvider = $this->helpersManager
                    ->getProviderResolver()
                    ->forServiceFromMetadataArray($rawConnectedServiceProvider->getServiceProviderMetadata());
                $user = new User($rawConnectedServiceProvider->getUserAttributes());

                $connectedServiceProviderBag->addOrReplace(
                    new ConnectedService(
                        $serviceProvider,
                        $rawConnectedServiceProvider->getNumberOfAuthentications(),
                        $rawConnectedServiceProvider->getLastAuthenticationAt(),
                        $rawConnectedServiceProvider->getFirstAuthenticationAt(),
                        $user
                    )
                );
            }
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error populating connected service provider bag. Error was: %s',
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $connectedServiceProviderBag;
    }

    /**
     * @throws StoreException
     */
    public function deleteDataOlderThan(DateTimeImmutable $dateTime): void
    {
        $this->repository->deleteConnectedServicesOlderThan($dateTime);
    }
}
