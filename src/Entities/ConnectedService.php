<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities;

use DateTimeImmutable;
use SimpleSAML\Module\profilepage\Entities\Interfaces\ServiceProviderInterface;

/**
 * Represents a Service VersionedDataProvider to which a user has authenticated at least once.
 */
class ConnectedService
{
    public function __construct(
        protected ServiceProviderInterface $serviceProvider,
        protected int $numberOfAuthentications,
        protected DateTimeImmutable $lastAuthenticationAt,
        protected DateTimeImmutable $firstAuthenticationAt,
        protected User $user
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
