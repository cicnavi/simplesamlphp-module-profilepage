<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services\JobRunner;

class State
{
    protected int $jobRunnerId;
    protected \DateTime $updatedAt;

    public function __construct(
        int $jobRunnerId,
        \DateTime $updatedAt = null
    ) {
        $this->jobRunnerId = $jobRunnerId;
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    /**
     * @return int
     */
    public function getJobRunnerId(): int
    {
        return $this->jobRunnerId;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime|null $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
