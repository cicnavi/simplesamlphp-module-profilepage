<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Interfaces\ServiceProviderInterface;

class Activity
{
    public function __construct(
        protected ServiceProviderInterface $serviceProvider,
        protected User $user,
        protected DateTimeImmutable $happenedAt,
        protected ?string $clientIpAddress,
        protected ?string $authenticationProtocolDesignation
    ) {
    }

    /**
     * @return ServiceProviderInterface
     */
    public function getServiceProvider(): ServiceProviderInterface
    {
        return $this->serviceProvider;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getHappenedAt(): DateTimeImmutable
    {
        return $this->happenedAt;
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
}
