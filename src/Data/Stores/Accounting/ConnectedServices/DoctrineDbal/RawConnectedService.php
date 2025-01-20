<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;

class RawConnectedService extends AbstractRawEntity
{
    protected int $numberOfAuthentications;
    protected DateTimeImmutable $lastAuthenticationAt;
    protected DateTimeImmutable $firstAuthenticationAt;
    protected array $serviceProviderMetadata;
    protected array $userAttributes;

    public function __construct(array $rawRow, AbstractPlatform $abstractPlatform, SerializerInterface $serializer)
    {
        parent::__construct($rawRow, $abstractPlatform, $serializer);

        /**
         * Data has been validated to have this shape.
         * @var array{
         *     number_of_authentications: numeric,
         *     last_authentication_at: numeric,
         *     first_authentication_at: numeric,
         *     sp_metadata: string,
         *     user_attributes: string,
         * } $rawRow
         */

        $this->numberOfAuthentications = (int)$rawRow[
        EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS
        ];

        $this->lastAuthenticationAt = $this->resolveDateTimeImmutable(
            (int)$rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT]
        );

        $this->firstAuthenticationAt = $this->resolveDateTimeImmutable(
            (int)$rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT]
        );

        $this->serviceProviderMetadata = $this->resolveServiceProviderMetadata(
            $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );

        $this->userAttributes = $this->resolveUserAttributes(
            $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );
    }

    /**
     * @return int
     */
    public function getNumberOfAuthentications(): int
    {
        return $this->numberOfAuthentications;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getLastAuthenticationAt(): DateTimeImmutable
    {
        return $this->lastAuthenticationAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getFirstAuthenticationAt(): DateTimeImmutable
    {
        return $this->firstAuthenticationAt;
    }

    /**
     * @return array
     */
    public function getServiceProviderMetadata(): array
    {
        return $this->serviceProviderMetadata;
    }

    /**
     * @return array
     */
    public function getUserAttributes(): array
    {
        return $this->userAttributes;
    }

    /**
     * @inheritDoc
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    protected function validate(array $rawRow): void
    {
        $columnsToCheck = [
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT,
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA,
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES,
        ];

        foreach ($columnsToCheck as $column) {
            if (empty($rawRow[$column])) {
                throw new UnexpectedValueException(sprintf('Column %s must be set.', $column));
            }
        }

        if (
            ! is_numeric($rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS])
        ) {
            $message = sprintf(
                'Column %s must be numeric.',
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS
            );
            throw new UnexpectedValueException($message);
        }

        /** @noinspection DuplicatedCode */
        if (! is_numeric($rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT])) {
            $message = sprintf(
                'Column %s must be numeric.',
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT
            );
            throw new UnexpectedValueException($message);
        }

        if (! is_numeric($rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT])) {
            $message = sprintf(
                'Column %s must be numeric.',
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT
            );
            throw new UnexpectedValueException($message);
        }

        if (! is_string($rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA])) {
            $message = sprintf(
                'Column %s must be string.',
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA
            );
            throw new UnexpectedValueException($message);
        }

        if (! is_string($rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES])) {
            $message = sprintf(
                'Column %s must be string.',
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES
            );
            throw new UnexpectedValueException($message);
        }
    }

    protected function resolveServiceProviderMetadata(string $serializedMetadata): array
    {
        /** @psalm-suppress MixedAssignment - we check the type manually */
        $metadata = $this->serializer->undo($serializedMetadata);

        if (is_array($metadata)) {
            return $metadata;
        }

        $message = sprintf('Metadata not in expected array format, got type %s.', gettype($metadata));
        throw new UnexpectedValueException($message);
    }

    protected function resolveUserAttributes(string $serializedUserAttributes): array
    {
        /** @psalm-suppress MixedAssignment - we check the type manually */
        $userAttributes = $this->serializer->undo($serializedUserAttributes);

        if (is_array($userAttributes)) {
            return $userAttributes;
        }

        $message = sprintf('User attributes not in expected array format, got type %s.', gettype($userAttributes));
        throw new UnexpectedValueException($message);
    }
}
