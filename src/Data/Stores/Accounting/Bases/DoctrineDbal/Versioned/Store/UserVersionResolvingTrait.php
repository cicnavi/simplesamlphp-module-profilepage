<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store;

use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use Throwable;

trait UserVersionResolvingTrait
{
    /**
     * @throws StoreException
     */
    public function resolveUserId(HashDecoratedState $hashDecoratedState): int
    {
        $userIdentifierAttributeName = $this->moduleConfiguration->getUserIdAttributeName();

        $userIdentifierValue = $hashDecoratedState->getState()->getFirstAttributeValue($userIdentifierAttributeName);
        if ($userIdentifierValue === null) {
            $message = sprintf('Attributes do not contain user ID attribute %s.', $userIdentifierAttributeName);
            throw new UnexpectedValueException($message);
        }

        $userIdentifierValueHashSha256 = $this->helpersManager->getHash()->getSha256($userIdentifierValue);

        // Check if it already exists.
        try {
            $result = $this->repository->getUser($userIdentifierValueHashSha256);
            $userId = $result->fetchOne();

            if ($userId !== false) {
                return (int)$userId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving user ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertUser($userIdentifierValue, $userIdentifierValueHashSha256);
        } catch (Throwable $exception) {
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
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving user ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function resolveUserVersionId(int $userId, HashDecoratedState $hashDecoratedState): int
    {
        $attributeArrayHashSha256 = $hashDecoratedState->getAttributesArrayHashSha256();

        // Check if it already exists.
        try {
            $result = $this->repository->getUserVersion($userId, $attributeArrayHashSha256);
            $userVersionId = $result->fetchOne();

            if ($userVersionId !== false) {
                return (int)$userVersionId;
            }
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving user version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        // Create new
        try {
            $this->repository->insertUserVersion(
                $userId,
                $this->serializer->do($hashDecoratedState->getState()->getAttributes()),
                $attributeArrayHashSha256
            );
        } catch (Throwable $exception) {
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
        } catch (Throwable $exception) {
            $message = sprintf('Error resolving user version ID. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
