<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Activity;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider;
use SimpleSAML\Module\accounting\Entities\ServiceProvider;
use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\HashDecoratedState;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawActivity;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawConnectedServiceProvider;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Stores\Interfaces\DataStoreInterface;

class Store extends AbstractStore implements DataStoreInterface
{
    protected Repository $repository;
    protected HelpersManager $helpersManager;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        Factory $connectionFactory = null,
        Repository $repository = null,
        HelpersManager $helpersManager = null
    ) {
        parent::__construct($moduleConfiguration, $logger, $connectionKey, $connectionType, $connectionFactory);

        $this->repository = $repository ?? new Repository($this->connection, $this->logger);
        $this->helpersManager = $helpersManager ?? new HelpersManager();
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
            $authenticationEvent->getState()->getClientIpAddress()
        );
    }

    /**
     * @throws StoreException
     */
    protected function resolveIdpId(HashDecoratedState $hashDecoratedState): int
    {
        $idpEntityIdHashSha256 = $hashDecoratedState->getIdentityProviderEntityIdHashSha256();

        // Check if it already exists.
        try {
            $result = $this->repository->getIdp($idpEntityIdHashSha256);
            $idpId = $result->fetchOne();

            if ($idpId !== false) {
                return (int)$idpId;
            }
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving Idp ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertIdp(
                $hashDecoratedState->getState()->getIdentityProviderEntityId(),
                $idpEntityIdHashSha256
            );
        } catch (\Throwable $exception) {
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
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving Idp ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    protected function resolveIdpVersionId(int $idpId, HashDecoratedState $hashDecoratedState): int
    {
        // Check if it already exists.
        $idpMetadataArrayHashSha256 = $hashDecoratedState->getIdentityProviderMetadataArrayHashSha256();

        try {
            $result = $this->repository->getIdpVersion($idpId, $idpMetadataArrayHashSha256);
            $idpVersionId = $result->fetchOne();

            if ($idpVersionId !== false) {
                return (int)$idpVersionId;
            }
        } catch (\Throwable $exception) {
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
        } catch (\Throwable $exception) {
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
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving Idp Version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    protected function resolveSpId(HashDecoratedState $hashDecoratedState): int
    {
        $spEntityIdHashSha256 = $hashDecoratedState->getServiceProviderEntityIdHashSha256();

        // Check if it already exists.
        try {
            $result = $this->repository->getSp($spEntityIdHashSha256);
            $spId = $result->fetchOne();

            if ($spId !== false) {
                return (int)$spId;
            }
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving SP ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertSp(
                $hashDecoratedState->getState()->getServiceProviderEntityId(),
                $spEntityIdHashSha256
            );
        } catch (\Throwable $exception) {
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
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving SP ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    protected function resolveSpVersionId(int $spId, HashDecoratedState $hashDecoratedState): int
    {
        // Check if it already exists.
        $spMetadataArrayHashSha256 = $hashDecoratedState->getServiceProviderMetadataArrayHashSha256();

        try {
            $result = $this->repository->getSpVersion($spId, $spMetadataArrayHashSha256);
            $spVersionId = $result->fetchOne();

            if ($spVersionId !== false) {
                return (int)$spVersionId;
            }
        } catch (\Throwable $exception) {
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
        } catch (\Throwable $exception) {
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
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving SP Version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    protected function resolveUserId(HashDecoratedState $hashDecoratedState): int
    {
        $userIdentifierAttributeName = $this->moduleConfiguration->getUserIdAttributeName();

        $userIdentifierValue = $hashDecoratedState->getState()->getAttributeValue($userIdentifierAttributeName);
        if ($userIdentifierValue === null) {
            $message = sprintf('Attributes do not contain user ID attribute %s.', $userIdentifierAttributeName);
            throw new UnexpectedValueException($message);
        }

        $userIdentifierValueHashSha256 = $this->helpersManager->getHashHelper()->getSha256($userIdentifierValue);

        // Check if it already exists.
        try {
            $result = $this->repository->getUser($userIdentifierValueHashSha256);
            $userId = $result->fetchOne();

            if ($userId !== false) {
                return (int)$userId;
            }
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving user ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertUser($userIdentifierValue, $userIdentifierValueHashSha256);
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error inserting new user, however, continuing in case of race condition. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getUser($userIdentifierValueHashSha256);
            $userIdNew = $result->fetchOne();

            if ($userIdNew !== false) {
                return (int)$userIdNew;
            }

            $message = sprintf(
                'Error fetching user even after insertion for identifier value hash SHA256 %s.',
                $userIdentifierValueHashSha256
            );
            throw new StoreException($message);
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving user ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    protected function resolveUserVersionId(int $userId, HashDecoratedState $hashDecoratedState): int
    {
        $attributeArrayHashSha256 = $hashDecoratedState->getAttributesArrayHashSha256();

        // Check if it already exists.
        try {
            $result = $this->repository->getUserVersion($userId, $attributeArrayHashSha256);
            $userVersionId = $result->fetchOne();

            if ($userVersionId !== false) {
                return (int)$userVersionId;
            }
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving user version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertUserVersion(
                $userId,
                serialize($hashDecoratedState->getState()->getAttributes()),
                $attributeArrayHashSha256
            );
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error inserting new user version, however, continuing in case of race condition. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->warning($message);
        }

        // Try again, this time it should exist...
        try {
            $result = $this->repository->getUserVersion($userId, $attributeArrayHashSha256);
            $userVersionIdNew = $result->fetchOne();

            if ($userVersionIdNew !== false) {
                return (int)$userVersionIdNew;
            }

            $message = sprintf(
                'Error fetching user version even after insertion for user ID %s.',
                $userId
            );
            throw new StoreException($message);
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving user version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    protected function resolveIdpSpUserVersionId(int $idpVersionId, int $spVersionId, int $userVersionId): int
    {
        // Check if it already exists.
        try {
            $result = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId);
            $IdpSpUserVersionId = $result->fetchOne();

            if ($IdpSpUserVersionId !== false) {
                return (int)$IdpSpUserVersionId;
            }
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving IdpSpUserVersion ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId);
        } catch (\Throwable $exception) {
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
        } catch (\Throwable $exception) {
            $message = sprintf('Error resolving IdpSpUserVersion ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getConnectedOrganizations(string $userIdentifierHashSha256): ConnectedServiceProvider\Bag
    {
        $connectedServiceProviderBag = new ConnectedServiceProvider\Bag();

        $results = $this->repository->getConnectedServiceProviders($userIdentifierHashSha256);

        if (empty($results)) {
            return $connectedServiceProviderBag;
        }

        try {
            $databasePlatform = $this->connection->dbal()->getDatabasePlatform();

            /** @var array $result */
            foreach ($results as $result) {
                $rawConnectedServiceProvider = new RawConnectedServiceProvider($result, $databasePlatform);

                $serviceProvider = new ServiceProvider($rawConnectedServiceProvider->getServiceProviderMetadata());
                $user = new User($rawConnectedServiceProvider->getUserAttributes());

                $connectedServiceProviderBag->addOrReplace(
                    new ConnectedServiceProvider(
                        $serviceProvider,
                        $rawConnectedServiceProvider->getNumberOfAuthentications(),
                        $rawConnectedServiceProvider->getLastAuthenticationAt(),
                        $rawConnectedServiceProvider->getFirstAuthenticationAt(),
                        $user
                    )
                );
            }
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error populating connected service provider bag. Error was: %s',
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $connectedServiceProviderBag;

        // TODO mivanci remove after unit tests
//        $authenticationEventsQueryBuilder = $this->connection->dbal()->createQueryBuilder();
//        $lastMetadataAndAttributesQueryBuilder = $this->connection->dbal()->createQueryBuilder();
//
//        /** @psalm-suppress TooManyArguments */
//        $authenticationEventsQueryBuilder->select(
//            'vs.entity_id AS sp_entity_id',
//            'COUNT(vae.id) AS number_of_authentications',
//            'MAX(vae.happened_at) AS last_authentication_at',
//            'MIN(vae.happened_at) AS first_authentication_at',
//        )->from('vds_authentication_event', 'vae')
//            ->leftJoin(
//                'vae',
//                'vds_idp_sp_user_version',
//                'visuv',
//                'vae.idp_sp_user_version_id = visuv.id'
//            )
//            ->leftJoin('visuv', 'vds_sp_version', 'vsv', 'visuv.sp_version_id = vsv.id')
//            ->leftJoin('vsv', 'vds_sp', 'vs', 'vsv.sp_id = vs.id')
//            ->leftJoin('visuv', 'vds_user_version', 'vuv', 'visuv.user_version_id = vuv.id')
//            ->leftJoin('vuv', 'vds_user', 'vu', 'vuv.user_id = vu.id')
//            ->where(
//                'vu.identifier_hash_sha256 = ' .
//                $authenticationEventsQueryBuilder->createNamedParameter($userIdentifierHashSha256)
//            )
//            ->groupBy('vs.id')
//            ->orderBy('number_of_authentications', 'DESC');
//
//        /** @psalm-suppress TooManyArguments */
//        $lastMetadataAndAttributesQueryBuilder->select(
//            'vs.entity_id AS sp_entity_id',
//            'vsv.metadata AS sp_metadata',
//            'vuv.attributes AS user_attributes',
//            //            'vsv.id AS sp_version_id',
//            //            'vuv.id AS user_version_id',
//        )->from('vds_authentication_event', 'vae')
//            ->leftJoin(
//                'vae',
//                'vds_idp_sp_user_version',
//                'visuv',
//                'vae.idp_sp_user_version_id = visuv.id'
//            )
//            ->leftJoin('visuv', 'vds_sp_version', 'vsv', 'visuv.sp_version_id = vsv.id')
//            ->leftJoin('vsv', 'vds_sp', 'vs', 'vsv.sp_id = vs.id')
//            ->leftJoin('visuv', 'vds_user_version', 'vuv', 'visuv.user_version_id = vuv.id')
//            ->leftJoin('vuv', 'vds_user', 'vu', 'vuv.user_id = vu.id')
//            ->leftJoin('vsv', 'vds_sp_version', 'vsv2', 'vsv.id = vsv2.id AND vsv.id < vsv2.id')
//            ->leftJoin('vuv', 'vds_user_version', 'vuv2', 'vuv.id = vuv2.id AND vuv.id < vuv2.id')
//            ->where(
//                'vu.identifier_hash_sha256 = ' .
//                $lastMetadataAndAttributesQueryBuilder->createNamedParameter($userIdentifierHashSha256)
//            )
//            ->andWhere('vsv2.id IS NULL')
//            ->andWhere('vuv2.id IS NULL');
//
//        try {
//            $numberOfAuthentications =
// $authenticationEventsQueryBuilder->executeQuery()->fetchAllAssociativeIndexed();
//            $lastMetadataAndAttributes =
//                $lastMetadataAndAttributesQueryBuilder->executeQuery()->fetchAllAssociativeIndexed();
//
//            return array_merge_recursive($numberOfAuthentications, $lastMetadataAndAttributes);
//        } catch (\Throwable $exception) {
//            $message = sprintf(
//                'Error executing query to get connected organizations. Error was: %s.',
//                $exception->getMessage()
//            );
//            throw new StoreException($message, (int)$exception->getCode(), $exception);
//        }
    }


    /**
     * @throws StoreException
     */
    public function getActivity(string $userIdentifierHashSha256, int $maxResults, int $firstResult): Activity\Bag
    {
        // TODO mivanci pagination
        $results =  $this->repository->getActivity($userIdentifierHashSha256, $maxResults, $firstResult);

        $activityBag = new Activity\Bag();

        if (empty($results)) {
            return $activityBag;
        }

        try {
            /** @var array $result */
            foreach ($results as $result) {
                $rawActivity = new RawActivity($result, $this->connection->dbal()->getDatabasePlatform());
                $serviceProvider = new ServiceProvider($rawActivity->getServiceProviderMetadata());
                $user = new User($rawActivity->getUserAttributes());

                $activityBag->add(
                    new Activity(
                        $serviceProvider,
                        $user,
                        $rawActivity->getHappenedAt(),
                        $rawActivity->getClientIpAddress()
                    )
                );
            }
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Error populating activity bag. Error was: %s',
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        return $activityBag;

        // TODO mivanci remove
//        $authenticationEventsQueryBuilder = $this->connection->dbal()->createQueryBuilder();
//
//        /** @psalm-suppress TooManyArguments */
//        $authenticationEventsQueryBuilder->select(
//            'vae.happened_at',
//            'vsv.metadata AS sp_metadata',
//            'vuv.attributes AS user_attributes'
//        )->from('vds_authentication_event', 'vae')
//            ->leftJoin(
//                'vae',
//                'vds_idp_sp_user_version',
//                'visuv',
//                'vae.idp_sp_user_version_id = visuv.id'
//            )
//            ->leftJoin('visuv', 'vds_sp_version', 'vsv', 'visuv.sp_version_id = vsv.id')
//            ->leftJoin('vsv', 'vds_sp', 'vs', 'vsv.sp_id = vs.id')
//            ->leftJoin('visuv', 'vds_user_version', 'vuv', 'visuv.user_version_id = vuv.id')
//            ->leftJoin('vuv', 'vds_user', 'vu', 'vuv.user_id = vu.id')
//            ->where(
//                'vu.identifier_hash_sha256 = ' .
//                $authenticationEventsQueryBuilder->createNamedParameter($userIdentifierHashSha256)
//            )
//            ->orderBy('vae.id', 'DESC');
//
//        try {
//            $numberOfAuthentications = $authenticationEventsQueryBuilder->executeQuery()->fetchAllAssociative();
//
//            return array_merge_recursive($numberOfAuthentications);
//        } catch (\Throwable $exception) {
//            $message = sprintf(
//                'Error executing query to get connected organizations. Error was: %s.',
//                $exception->getMessage()
//            );
//            throw new StoreException($message, (int)$exception->getCode(), $exception);
//        }
    }
}
