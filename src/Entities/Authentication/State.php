<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use Throwable;

class State
{
    public const KEY_ATTRIBUTES = 'Attributes';
    public const KEY_AUTHENTICATION_INSTANT = 'AuthnInstant';
    public const KEY_IDENTITY_PROVIDER_METADATA = 'IdPMetadata';
    public const KEY_SOURCE = 'Source';
    public const KEY_SERVICE_PROVIDER_METADATA = 'SPMetadata';
    public const KEY_DESTINATION = 'Destination';

    public const KEY_ACCOUNTING = 'accounting';
    public const ACCOUNTING_KEY_CLIENT_IP_ADDRESS = 'client_ip_address';

    protected string $identityProviderEntityId;
    protected string $serviceProviderEntityId;
    protected array $attributes;
    protected DateTimeImmutable $createdAt;
    protected ?DateTimeImmutable $authenticationInstant;
    protected array $identityProviderMetadata;
    protected array $serviceProviderMetadata;
    protected ?string $clientIpAddress;
    protected HelpersManager $helpersManager;

    public function __construct(
        array $state,
        DateTimeImmutable $createdAt = null,
        HelpersManager $helpersManager = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->identityProviderMetadata = $this->resolveIdentityProviderMetadata($state);
        $this->identityProviderEntityId = $this->resolveIdentityProviderEntityId();
        $this->serviceProviderMetadata = $this->resolveServiceProviderMetadata($state);
        $this->serviceProviderEntityId = $this->resolveServiceProviderEntityId();
        $this->attributes = $this->resolveAttributes($state);
        $this->authenticationInstant = $this->resolveAuthenticationInstant($state);
        $this->clientIpAddress = $this->resolveClientIpAddress($state);
    }

    protected function resolveIdentityProviderEntityId(): string
    {
        if (
            !empty($this->identityProviderMetadata[AbstractProvider::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->identityProviderMetadata[AbstractProvider::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->identityProviderMetadata[AbstractProvider::METADATA_KEY_ENTITY_ID];
        }

        throw new UnexpectedValueException('IdP metadata array does not contain entity ID.');
    }

    protected function resolveServiceProviderEntityId(): string
    {
        if (
            !empty($this->serviceProviderMetadata[AbstractProvider::METADATA_KEY_ENTITY_ID]) &&
            is_string($this->serviceProviderMetadata[AbstractProvider::METADATA_KEY_ENTITY_ID])
        ) {
            return $this->serviceProviderMetadata[AbstractProvider::METADATA_KEY_ENTITY_ID];
        }

        throw new UnexpectedValueException('SP metadata array does not contain entity ID.');
    }

    protected function resolveAttributes(array $state): array
    {
        if (empty($state[self::KEY_ATTRIBUTES]) || !is_array($state[self::KEY_ATTRIBUTES])) {
            throw new UnexpectedValueException('State array does not contain user attributes.');
        }

        return $state[self::KEY_ATTRIBUTES];
    }

    public function getIdentityProviderEntityId(): string
    {
        return $this->identityProviderEntityId;
    }

    public function getServiceProviderEntityId(): string
    {
        return $this->serviceProviderEntityId;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttributeValue(string $attributeName): ?string
    {
        if (!empty($this->attributes[$attributeName]) && is_array($this->attributes[$attributeName])) {
            return (string)reset($this->attributes[$attributeName]);
        }

        return null;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    protected function resolveAuthenticationInstant(array $state): ?DateTimeImmutable
    {
        if (empty($state[self::KEY_AUTHENTICATION_INSTANT])) {
            return null;
        }

        $authInstant = (string)$state[self::KEY_AUTHENTICATION_INSTANT];

        try {
            return new DateTimeImmutable('@' . $authInstant);
        } catch (Throwable $exception) {
            $message = sprintf(
                'Unable to create DateTimeImmutable using AuthInstant value \'%s\'. Error was: %s.',
                $authInstant,
                $exception->getMessage()
            );
            throw new UnexpectedValueException($message);
        }
    }

    public function getAuthenticationInstant(): ?DateTimeImmutable
    {
        return $this->authenticationInstant;
    }

    protected function resolveIdentityProviderMetadata(array $state): array
    {
        if (
            !empty($state[self::KEY_IDENTITY_PROVIDER_METADATA]) &&
            is_array($state[self::KEY_IDENTITY_PROVIDER_METADATA])
        ) {
            return $state[self::KEY_IDENTITY_PROVIDER_METADATA];
        } elseif (!empty($state[self::KEY_SOURCE]) && is_array($state[self::KEY_SOURCE])) {
            return $state[self::KEY_SOURCE];
        }

        throw new UnexpectedValueException('State array does not contain IdP metadata.');
    }

    protected function resolveServiceProviderMetadata(array $state): array
    {
        if (
            !empty($state[self::KEY_SERVICE_PROVIDER_METADATA]) &&
            is_array($state[self::KEY_SERVICE_PROVIDER_METADATA])
        ) {
            return $state[self::KEY_SERVICE_PROVIDER_METADATA];
        } elseif (!empty($state[self::KEY_DESTINATION]) && is_array($state[self::KEY_DESTINATION])) {
            return $state[self::KEY_DESTINATION];
        }

        throw new UnexpectedValueException('State array does not contain SP metadata.');
    }

    /**
     * @return array
     */
    public function getIdentityProviderMetadata(): array
    {
        return $this->identityProviderMetadata;
    }

    /**
     * @return array
     */
    public function getServiceProviderMetadata(): array
    {
        return $this->serviceProviderMetadata;
    }

    protected function resolveClientIpAddress(array $state): ?string
    {
        return $this->helpersManager->getNetworkHelper()->resolveClientIpAddress(
            isset($state[self::KEY_ACCOUNTING][self::ACCOUNTING_KEY_CLIENT_IP_ADDRESS]) ?
                (string)$state[self::KEY_ACCOUNTING][self::ACCOUNTING_KEY_CLIENT_IP_ADDRESS]
                : null
        );
    }

    /**
     * @return string|null
     */
    public function getClientIpAddress(): ?string
    {
        return $this->clientIpAddress;
    }
}
