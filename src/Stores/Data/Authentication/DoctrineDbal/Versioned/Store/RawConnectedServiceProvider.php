<?php

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity;

class RawConnectedServiceProvider extends AbstractRawEntity
{
    protected int $numberOfAuthentications;
    protected DateTimeImmutable $lastAuthenticationAt;
    protected DateTimeImmutable $firstAuthenticationAt;
    protected array $serviceProviderMetadata;
    protected array $userAttributes;

    public function __construct(array $rawRow, AbstractPlatform $abstractPlatform)
    {
        parent::__construct($rawRow, $abstractPlatform);

        $this->numberOfAuthentications = (int)$rawRow[
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS
        ];

        $this->lastAuthenticationAt = $this->resolveDateTimeImmutable(
            $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT]
        );

        $this->firstAuthenticationAt = $this->resolveDateTimeImmutable(
            $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT]
        );

        $this->serviceProviderMetadata = $this->resolveServiceProviderMetadata(
            (string)$rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA]
        );

        $this->userAttributes = $this->resolveUserAttributes(
            (string)$rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES]
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
     */
    protected function validate(array $rawRow): void
    {
        $columnsToCheck = [
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT,
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT,
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA,
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES,
        ];

        foreach ($columnsToCheck as $column) {
            if (empty($rawRow[$column])) {
                throw new UnexpectedValueException(sprintf('Column %s must be set.', $column));
            }
        }

        if (
            ! is_numeric($rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS])
        ) {
            throw new UnexpectedValueException(
                sprintf(
                    'Column %s must be numeric.',
                    TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS
                )
            );
        }

        if (! is_string($rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT])) {
            throw new UnexpectedValueException(
                sprintf(
                    'Column %s must be string.',
                    TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT
                )
            );
        }

        if (! is_string($rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT])) {
            throw new UnexpectedValueException(
                sprintf(
                    'Column %s must be string.',
                    TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT
                )
            );
        }

        if (! is_string($rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA])) {
            throw new UnexpectedValueException(
                sprintf(
                    'Column %s must be string.',
                    TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA
                )
            );
        }

        if (! is_string($rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES])) {
            throw new UnexpectedValueException(
                sprintf(
                    'Column %s must be string.',
                    TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES
                )
            );
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