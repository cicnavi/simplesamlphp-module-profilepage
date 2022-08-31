<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Helpers\HashHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\HashDecoratedState;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;
use SimpleSAML\Module\accounting\Stores\Interfaces\DataStoreInterface;

class Store extends AbstractStore implements DataStoreInterface
{
    protected Repository $repository;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        Factory $connectionFactory,
        string $connectionKey = null,
        Repository $repository = null
    ) {
        parent::__construct($moduleConfiguration, $logger, $connectionFactory, $connectionKey);

        $this->repository = $repository ?? new Repository($this->connection, $this->logger);
    }

    /**
     * Build store instance.
     * @throws StoreException
     */
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self {
        return new self(
            $moduleConfiguration,
            $logger,
            new Factory($moduleConfiguration, $logger),
            $connectionKey
        );
    }

    /** @psalm-suppress UnusedVariable TODO mivanci remove */
    public function persist(Event $authenticationEvent): void
    {
        // Get IdP ID
        $hashDecoratedState = new HashDecoratedState($authenticationEvent->getState());
        $idpEntityId = $hashDecoratedState->getState()->getIdpEntityId();
        $idpEntityIdHash = $hashDecoratedState->getIdpEntityIdHashSha256();


        $idpId = $this->resolveIdpId($hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    protected function resolveIdpId(HashDecoratedState $hashDecoratedState): int
    {
        $idpEntityIdHashSha256 = $hashDecoratedState->getIdpEntityIdHashSha256();

        /** @var string|bool $idpId */

        // Check if it already exists.
        try {
            $result = $this->repository->getIdpByEntityIdHashSha256($idpEntityIdHashSha256);

            if (($idpId = $result->fetchOne()) !== false) {
                return (int)$idpId;
            }
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving Idp ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertIdp($hashDecoratedState->getState()->getIdpEntityId(), $idpEntityIdHashSha256);
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error inserting new IdP, however, continuing because of possible race condition. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getIdpByEntityIdHashSha256($idpEntityIdHashSha256);

            /** @psalm-suppress MixedAssignment */
            if (($idpId = $result->fetchOne()) !== false) {
                return (int)$idpId;
            }

            $message = sprintf(
                'Error fetching IdP ID even after insertion for entity ID hash SHA256 %s.',
                $idpEntityIdHashSha256
            );
            throw new StoreException($message);
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving Idp ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
