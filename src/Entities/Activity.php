<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Interfaces\ServiceProviderInterface;

class Activity
{
    protected ServiceProviderInterface $serviceProvider;
    protected User $user;
    protected DateTimeImmutable $happenedAt;
    protected ?string $clientIpAddress;
    protected ?string $authenticationProtocolDesignation;

    public function __construct(
        ServiceProviderInterface $serviceProvider,
        User $user,
        DateTimeImmutable $happenedAt,
        ?string $clientIpAddress,
        ?string $authenticationProtocolDesignation
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->user = $user;
        $this->happenedAt = $happenedAt;
        $this->clientIpAddress = $clientIpAddress;
        $this->authenticationProtocolDesignation = $authenticationProtocolDesignation;
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
