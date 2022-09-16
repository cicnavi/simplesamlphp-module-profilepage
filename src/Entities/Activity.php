<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

use DateTimeImmutable;

class Activity
{
    protected ServiceProvider $serviceProvider;
    protected User $user;
    protected DateTimeImmutable $happenedAt;

    public function __construct(
        ServiceProvider $serviceProvider,
        User $user,
        DateTimeImmutable $happenedAt
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->user = $user;
        $this->happenedAt = $happenedAt;
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
}
