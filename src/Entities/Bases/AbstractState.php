<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Bases;

use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\profilepage\Entities\Interfaces\StateInterface;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use DateTimeImmutable;
use SimpleSAML\Module\profilepage\Traits\HasUserAttributesTrait;
use Throwable;

abstract class AbstractState implements StateInterface
{
    use HasUserAttributesTrait;

    public const KEY_ATTRIBUTES = 'Attributes';
    public const KEY_ACCOUNTING = 'accounting';
    public const ACCOUNTING_KEY_CLIENT_IP_ADDRESS = 'client_ip_address';
    public const KEY_AUTHENTICATION_INSTANT = 'AuthnInstant';

    protected string $identityProviderEntityId;
    protected string $serviceProviderEntityId;
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

    abstract protected function resolveIdentityProviderMetadata(array $state): array;
    abstract protected function resolveIdentityProviderEntityId(): string;
    abstract protected function resolveServiceProviderMetadata(array $state): array;
    abstract protected function resolveServiceProviderEntityId(): string;
    abstract public function getAuthenticationProtocol(): AuthenticationProtocolInterface;

    protected function resolveAttributes(array $state): array
    {
        if (empty($state[self::KEY_ATTRIBUTES]) || !is_array($state[self::KEY_ATTRIBUTES])) {
            throw new UnexpectedValueException('State array does not contain user attributes.');
        }

        return $state[self::KEY_ATTRIBUTES];
    }

    protected function resolveClientIpAddress(array $state): ?string
    {
        return $this->helpersManager->getNetwork()->resolveClientIpAddress(
            isset($state[self::KEY_ACCOUNTING][self::ACCOUNTING_KEY_CLIENT_IP_ADDRESS]) ?
                (string)$state[self::KEY_ACCOUNTING][self::ACCOUNTING_KEY_CLIENT_IP_ADDRESS]
                : null
        );
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

    public function getIdentityProviderEntityId(): string
    {
        return $this->identityProviderEntityId;
    }

    public function getServiceProviderEntityId(): string
    {
        return $this->serviceProviderEntityId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAuthenticationInstant(): ?DateTimeImmutable
    {
        return $this->authenticationInstant;
    }

    public function getIdentityProviderMetadata(): array
    {
        return $this->identityProviderMetadata;
    }

    public function getServiceProviderMetadata(): array
    {
        return $this->serviceProviderMetadata;
    }

    public function getClientIpAddress(): ?string
    {
        return $this->clientIpAddress;
    }
}
