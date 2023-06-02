<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store as VersionedStore;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\StoreInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use Throwable;

class Store extends AbstractStore implements StoreInterface
{
    use VersionedStore\UserVersionResolvingTrait;

    protected HelpersManager $helpersManager;
    private Repository $repository;

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
        parent::__construct($moduleConfiguration, $logger, $connectionKey, $connectionType, $connectionFactory);

        $this->helpersManager = $helpersManager ?? new HelpersManager();
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
    public function resolveSpId(HashDecoratedState $hashDecoratedState): int
    {
        $spEntityIdHashSha256 = $hashDecoratedState->getServiceProviderEntityIdHashSha256();

        // Check if it already exists.
        try {
            $result = $this->repository->getSp($spEntityIdHashSha256);
            $sp = $result->fetchAssociative();

            if ($sp !== false) {
                $spId = (int)$sp[TableConstants::TABLE_SP_COLUMN_NAME_ID];
                // If metadata hash is different, update metadata.
                if (
                    $sp[TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256] !==
                    $hashDecoratedState->getServiceProviderMetadataArrayHashSha256()
                ) {
                    $this->repository->updateSp(
                        $spId,
                        serialize($hashDecoratedState->getState()->getServiceProviderMetadata()),
                        $hashDecoratedState->getServiceProviderMetadataArrayHashSha256()
                    );
                }
                return $spId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving SP ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertSp(
                $hashDecoratedState->getState()->getServiceProviderEntityId(),
                $spEntityIdHashSha256,
                serialize($hashDecoratedState->getState()->getServiceProviderMetadata()),
                $hashDecoratedState->getServiceProviderMetadataArrayHashSha256()
            );
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error inserting new SP, however, continuing in case of race condition. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getSp($spEntityIdHashSha256);
            $spIdNew = $result->fetchOne();

            if ($spIdNew !== false) {
                return (int)$spIdNew;
            }

            $message = sprintf(
                'Error fetching SP ID even after insertion for entity ID hash SHA256 %s.',
                $spEntityIdHashSha256
            );
            throw new StoreException($message);
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving SP ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
