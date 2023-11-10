<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store as BaseStore;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Traits\Store\GettableConnectedServicesTrait;
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

        $spId = $this->resolveSpId($hashDecoratedState);
        $userId = $this->resolveUserId($hashDecoratedState);
        $userVersionId = $this->resolveUserVersionId($userId, $hashDecoratedState);

        /** @psalm-suppress MixedAssignment */
        $connectedServiceId = $this->repository->getConnectedService($spId, $userId)->fetchOne();

        if ($connectedServiceId !== false) {
            $this->repository->updateConnectedServiceVersionCount(
                (int)$connectedServiceId,
                $userVersionId,
                $authenticationEvent->getHappenedAt()
            );
        } else {
            $this->repository->insertConnectedService(
                $spId,
                $userId,
                $userVersionId,
                $authenticationEvent->getHappenedAt()
            );
        }
    }

    /**
     * @throws StoreException
     */
    public function deleteDataOlderThan(DateTimeImmutable $dateTime): void
    {
        $this->repository->deleteConnectedServicesOlderThan($dateTime);
    }
}
