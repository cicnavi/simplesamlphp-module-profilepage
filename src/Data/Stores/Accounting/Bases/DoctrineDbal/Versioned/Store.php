<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository;
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
    use Store\UserVersionResolvingTrait;

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
    public function resolveIdpId(HashDecoratedState $hashDecoratedState): int
    {
        $idpEntityIdHashSha256 = $hashDecoratedState->getIdentityProviderEntityIdHashSha256();

        // Check if it already exists.
        try {
            $result = $this->repository->getIdp($idpEntityIdHashSha256);
            $idpId = $result->fetchOne();

            if ($idpId !== false) {
                return (int)$idpId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving Idp ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertIdp(
                $hashDecoratedState->getState()->getIdentityProviderEntityId(),
                $idpEntityIdHashSha256
            );
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error inserting new IdP, however, continuing in case of race condition. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getIdp($idpEntityIdHashSha256);
            $idpIdNew = $result->fetchOne();

            if ($idpIdNew !== false) {
                return (int)$idpIdNew;
            }

            $message = sprintf(
                'Error fetching IdP ID even after insertion for entity ID hash SHA256 %s.',
                $idpEntityIdHashSha256
            );
            throw new StoreException($message);
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving Idp ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function resolveIdpVersionId(int $idpId, HashDecoratedState $hashDecoratedState): int
    {
        // Check if it already exists.
        $idpMetadataArrayHashSha256 = $hashDecoratedState->getIdentityProviderMetadataArrayHashSha256();

        try {
            $result = $this->repository->getIdpVersion($idpId, $idpMetadataArrayHashSha256);
            $idpVersionId = $result->fetchOne();

            if ($idpVersionId !== false) {
                return (int)$idpVersionId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving IdP Version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertIdpVersion(
                $idpId,
                serialize($hashDecoratedState->getState()->getIdentityProviderMetadata()),
                $idpMetadataArrayHashSha256
            );
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error inserting new IdP Version, however, continuing in case of race condition. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getIdpVersion($idpId, $idpMetadataArrayHashSha256);
            $idpVersionIdNew = $result->fetchOne();

            if ($idpVersionIdNew !== false) {
                return (int)$idpVersionIdNew;
            }

            $message = sprintf(
                'Error fetching IdP ID Version even after insertion for Idp ID %s.',
                $idpId
            );
            throw new StoreException($message);
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving Idp Version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
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
            $spId = $result->fetchOne();

            if ($spId !== false) {
                return (int)$spId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving SP ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertSp(
                $hashDecoratedState->getState()->getServiceProviderEntityId(),
                $spEntityIdHashSha256
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

    /**
     * @throws StoreException
     */
    public function resolveSpVersionId(int $spId, HashDecoratedState $hashDecoratedState): int
    {
        // Check if it already exists.
        $spMetadataArrayHashSha256 = $hashDecoratedState->getServiceProviderMetadataArrayHashSha256();

        try {
            $result = $this->repository->getSpVersion($spId, $spMetadataArrayHashSha256);
            $spVersionId = $result->fetchOne();

            if ($spVersionId !== false) {
                return (int)$spVersionId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving SP Version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertSpVersion(
                $spId,
                serialize($hashDecoratedState->getState()->getServiceProviderMetadata()),
                $spMetadataArrayHashSha256
            );
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error inserting new SP Version, however, continuing in case of race condition. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getSpVersion($spId, $spMetadataArrayHashSha256);
            $spVersionIdNew = $result->fetchOne();

            if ($spVersionIdNew !== false) {
                return (int)$spVersionIdNew;
            }

            $message = sprintf(
                'Error fetching SP Version even after insertion for SP ID %s.',
                $spId
            );
            throw new StoreException($message);
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving SP Version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function resolveIdpSpUserVersionId(int $idpVersionId, int $spVersionId, int $userVersionId): int
    {
        // Check if it already exists.
        try {
            $result = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId);
            $IdpSpUserVersionId = $result->fetchOne();

            if ($IdpSpUserVersionId !== false) {
                return (int)$IdpSpUserVersionId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving IdpSpUserVersion ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId);
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error inserting new IdpSpUserVersion, however, continuing in case of race condition. ' .
                'Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId);
            $IdpSpUserVersionIdNew = $result->fetchOne();

            if ($IdpSpUserVersionIdNew !== false) {
                return (int)$IdpSpUserVersionIdNew;
            }

            $message = sprintf(
                'Error fetching IdpSpUserVersion ID even after insertion for IdpVersion %s, SpVersion ID %s and ' .
                'UserVersion ID %s.',
                $idpVersionId,
                $spVersionId,
                $userVersionId
            );
            throw new StoreException($message);
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving IdpSpUserVersion ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
