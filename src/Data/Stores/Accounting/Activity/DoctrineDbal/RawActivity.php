<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Interfaces\SerializerInterface;

class RawActivity extends AbstractRawEntity
{
    protected array $serviceProviderMetadata;
    protected array $userAttributes;
    protected DateTimeImmutable $happenedAt;
    protected ?string $clientIpAddress;
    protected ?string $authenticationProtocolDesignation;

    public function __construct(
        array $rawRow,
        AbstractPlatform $abstractPlatform,
        SerializerInterface $serializer
    ) {
        parent::__construct($rawRow, $abstractPlatform, $serializer);

        $this->serviceProviderMetadata = $this->resolveServiceProviderMetadata(
            (string)$rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA]
        );

        $this->userAttributes = $this->resolveUserAttributes(
            (string)$rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES]
        );

        $this->happenedAt = $this->resolveDateTimeImmutable(
            (int)$rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT]
        );

        $this->clientIpAddress = empty($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS]) ?
            null :
            (string)$rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS];

        $this->authenticationProtocolDesignation =
            empty($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION]) ?
            null :
            (string)$rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION];
    }

    /**
     * @return DateTimeImmutable
     */
    public function getHappenedAt(): DateTimeImmutable
    {
        return $this->happenedAt;
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
     * @return string|null
     */
    public function getClientIpAddress(): ?string
    {
        return $this->clientIpAddress;
    }

    /**
     * @return string|null
     */
    public function getAuthenticationProtocolDesignation(): ?string
    {
        return $this->authenticationProtocolDesignation;
    }

    /**
     * @inheritDoc
     */
    protected function validate(array $rawRow): void
    {
        $columnsToCheck = [
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA,
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES,
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT,
        ];

        foreach ($columnsToCheck as $column) {
            if (empty($rawRow[$column])) {
                throw new UnexpectedValueException(sprintf('Column %s must be set.', $column));
            }
        }

        /** @noinspection DuplicatedCode */
        if (! is_string($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA])) {
            $message = sprintf(
                'Column %s must be string.',
                EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA
            );
            throw new UnexpectedValueException($message);
        }

        if (! is_string($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES])) {
            $message = sprintf(
                'Column %s must be string.',
                EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES
            );
            throw new UnexpectedValueException($message);
        }

        if (! is_numeric($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT])) {
            $message = sprintf(
                'Column %s must be numeric.',
                EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT
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
