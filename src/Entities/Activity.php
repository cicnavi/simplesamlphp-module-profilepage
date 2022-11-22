<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use DateTimeImmutable;

class Activity
{
    protected ServiceProvider $serviceProvider;
    protected User $user;
    protected DateTimeImmutable $happenedAt;
    protected ?string $clientIpAddress;

    public function __construct(
        ServiceProvider $serviceProvider,
        User $user,
        DateTimeImmutable $happenedAt,
        ?string $clientIpAddress
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->user = $user;
        $this->happenedAt = $happenedAt;
        $this->clientIpAddress = $clientIpAddress;
    }

    /**
     * @return ServiceProvider
     */
    public function getServiceProvider(): ServiceProvider
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
}
