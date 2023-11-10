<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Interfaces;

use DateTimeImmutable;

interface StateInterface
{
    public function getIdentityProviderEntityId(): string;
    public function getServiceProviderEntityId(): string;
    public function getAttributes(): array;
    public function getFirstAttributeValue(string $attributeName): ?string;
    public function getCreatedAt(): DateTimeImmutable;
    public function getAuthenticationInstant(): ?DateTimeImmutable;
    public function getIdentityProviderMetadata(): array;
    public function getServiceProviderMetadata(): array;
    public function getClientIpAddress(): ?string;
    public function getAuthenticationProtocol(): AuthenticationProtocolInterface;
}
