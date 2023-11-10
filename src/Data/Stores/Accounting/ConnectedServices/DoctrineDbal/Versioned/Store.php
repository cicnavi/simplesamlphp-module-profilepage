<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store as BaseStore;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Traits\Store\GettableConnectedServicesTrait;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\profilepage\Data\Stores\Interfaces\ConnectedServicesInterface;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;

class Store extends BaseStore implements ConnectedServicesInterface
{
    use GettableConnectedServicesTrait;

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
        Repository $repository = null,
        SerializerInterface $serializer = null,
    ) {
        parent::__construct(
            $moduleConfiguration,
            $logger,
            $connectionKey,
            $connectionType,
            $connectionFactory,
            $helpersManager,
            $repository,
            $serializer,
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
     * @throws StoreException|Exception
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
    public function deleteDataOlderThan(DateTimeImmutable $dateTime): void
    {
        $this->repository->deleteConnectedServicesOlderThan($dateTime);
    }
}
