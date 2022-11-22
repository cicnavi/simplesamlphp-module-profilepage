<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity;

class RawActivity extends AbstractRawEntity
{
    protected array $serviceProviderMetadata;
    protected array $userAttributes;
    protected DateTimeImmutable $happenedAt;
    protected ?string $clientIpAddress;

    public function __construct(array $rawRow, AbstractPlatform $abstractPlatform)
    {
        parent::__construct($rawRow, $abstractPlatform);

        $this->serviceProviderMetadata = $this->resolveServiceProviderMetadata(
            (string)$rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA]
        );

        $this->userAttributes = $this->resolveUserAttributes(
            (string)$rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES]
        );

        $this->happenedAt = $this->resolveDateTimeImmutable(
            $rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT]
        );

        $this->clientIpAddress = empty($rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS]) ?
            null :
            (string)$rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS];
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
     * @inheritDoc
     */
    protected function validate(array $rawRow): void
    {
        $columnsToCheck = [
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA,
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES,
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT,
        ];

        foreach ($columnsToCheck as $column) {
            if (empty($rawRow[$column])) {
                throw new UnexpectedValueException(sprintf('Column %s must be set.', $column));
            }
        }

        if (! is_string($rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA])) {
            $message = sprintf(
                'Column %s must be string.',
                TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA
            );
            throw new UnexpectedValueException($message);
        }

        if (! is_string($rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES])) {
            $message = sprintf(
                'Column %s must be string.',
                TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES
            );
            throw new UnexpectedValueException($message);
        }

        if (! is_string($rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT])) {
            $message = sprintf(
                'Column %s must be string.',
                TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT
            );
            throw new UnexpectedValueException($message);
        }
    }

    protected function resolveServiceProviderMetadata(string $serializedMetadata): array
    {
        /** @psalm-suppress MixedAssignment - we check the type manually */
        $metadata = unserialize($serializedMetadata);

        if (is_array($metadata)) {
            return $metadata;
        }

        $message = sprintf('Metadata not in expected array format, got type %s.', gettype($metadata));
        throw new UnexpectedValueException($message);
    }

    protected function resolveUserAttributes(string $serializedUserAttributes): array
    {
        /** @psalm-suppress MixedAssignment - we check the type manually */
        $userAttributes = unserialize($serializedUserAttributes);

        if (is_array($userAttributes)) {
            return $userAttributes;
        }

        $message = sprintf('User attributes not in expected array format, got type %s.', gettype($userAttributes));
        throw new UnexpectedValueException($message);
    }
}
