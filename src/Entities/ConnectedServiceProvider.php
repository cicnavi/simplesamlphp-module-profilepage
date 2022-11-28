<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use DateTimeImmutable;

/**
 * Represents a Service Provider to which a user has authenticated at least once.
 */
class ConnectedServiceProvider
{
    protected ServiceProvider $serviceProvider;
    protected int $numberOfAuthentications;
    protected DateTimeImmutable $lastAuthenticationAt;
    protected DateTimeImmutable $firstAuthenticationAt;
    protected User $user;

    /**
     * @param ServiceProvider $serviceProvider
     * @param int $numberOfAuthentications
     * @param DateTimeImmutable $lastAuthenticationAt
     * @param DateTimeImmutable $firstAuthenticationAt
     * @param User $user
     */
    public function __construct(
        ServiceProvider $serviceProvider,
        int $numberOfAuthentications,
        DateTimeImmutable $lastAuthenticationAt,
        DateTimeImmutable $firstAuthenticationAt,
        User $user
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->numberOfAuthentications = $numberOfAuthentications;
        $this->lastAuthenticationAt = $lastAuthenticationAt;
        $this->firstAuthenticationAt = $firstAuthenticationAt;
        $this->user = $user;
    }

    /**
     * @return ServiceProvider
     */
    public function getServiceProvider(): ServiceProvider
    {
        return $this->serviceProvider;
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
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
