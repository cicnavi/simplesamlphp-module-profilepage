<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Interfaces\ServiceProviderInterface;

/**
 * Represents a Service VersionedDataProvider to which a user has authenticated at least once.
 */
class ConnectedService
{
    protected ServiceProviderInterface $serviceProvider;
    protected int $numberOfAuthentications;
    protected DateTimeImmutable $lastAuthenticationAt;
    protected DateTimeImmutable $firstAuthenticationAt;
    protected User $user;

    /**
     * @param ServiceProviderInterface $serviceProvider
     * @param int $numberOfAuthentications
     * @param DateTimeImmutable $lastAuthenticationAt
     * @param DateTimeImmutable $firstAuthenticationAt
     * @param User $user
     */
    public function __construct(
        ServiceProviderInterface $serviceProvider,
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
     * @return ServiceProviderInterface
     */
    public function getServiceProvider(): ServiceProviderInterface
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