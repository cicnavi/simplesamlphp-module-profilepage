<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services\JobRunner;

class State
{
    protected int $jobRunnerId;
    protected ?\DateTimeImmutable $startedAt;
    protected ?\DateTimeImmutable $updatedAt;
    protected ?\DateTimeImmutable $endedAt = null;
    protected int $successfulJobsProcessed = 0;
    protected int $failedJobsProcessed = 0;

    public function __construct(
        int $jobRunnerId,
        \DateTimeImmutable $startedAt = null,
        \DateTimeImmutable $updatedAt = null
    ) {
        $this->jobRunnerId = $jobRunnerId;
        $this->startedAt = $startedAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return int
     */
    public function getJobRunnerId(): int
    {
        return $this->jobRunnerId;
    }

    /**
     * @return ?\DateTimeImmutable
     */
    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * Set startedAt if not already set.
     * @param \DateTimeImmutable $startedAt
     * @return bool True if set, false otherwise.
     */
    public function setStartedAt(\DateTimeImmutable $startedAt): bool
    {
        if ($this->startedAt === null) {
            $this->startedAt = $startedAt;
            return true;
        }

        return false;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeImmutable $updatedAt
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Set endedAt if not already set.
     * @param \DateTimeImmutable $endedAt
     * @return bool True if set, false otherwise.
     */
    public function setEndedAt(\DateTimeImmutable $endedAt): bool
    {
        if ($this->endedAt === null) {
            $this->endedAt = $endedAt;
            return true;
        }

        return false;
    }

    /**
     * @return ?\DateTimeImmutable
     */
    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function hasRunStarted(): bool
    {
        return $this->startedAt !== null;
    }

    public function incrementSuccessfulJobsProcessed(): void
    {
        $this->successfulJobsProcessed++;
    }

    public function incrementFailedJobsProcessed(): void
    {
        $this->failedJobsProcessed++;
    }

    /**
     * @return int
     */
    public function getSuccessfulJobsProcessed(): int
    {
        return $this->successfulJobsProcessed;
    }

    /**
     * @return int
     */
    public function getFailedJobsProcessed(): int
    {
        return $this->failedJobsProcessed;
    }

    public function isStale(\DateInterval $threshold): bool
    {
        // TODO mivanci if updatedAt is smaller than threshold

        if ($this->updatedAt === null) {
            return false;
        }

        $minDateTime = (new \DateTimeImmutable())->sub($threshold);

        if ($this->getUpdatedAt() < $minDateTime) {
            return true;
        }

        return false;
    }
}
