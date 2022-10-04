<?php

namespace SimpleSAML\Module\accounting\Services;

class RateLimiter
{
    public const DEFAULT_MAX_PAUSE_DURATION = 'PT10M';
    public const DEFAULT_MAX_BACKOFF_PAUSE_DURATION = 'PT1M';

    protected int $maxPauseInSeconds;
    protected int $maxBackoffPauseInSeconds;
    protected int $currentBackoffPauseInSeconds = 1;

    public function __construct(
        \DateInterval $maxPauseInterval = null,
        \DateInterval $maxBackoffInterval = null
    ) {
        $this->maxPauseInSeconds = $this->dateIntervalToSeconds(
            $maxPauseInterval ?? new \DateInterval(self::DEFAULT_MAX_PAUSE_DURATION)
        );
        $this->maxBackoffPauseInSeconds = $this->dateIntervalToSeconds(
            $maxBackoffInterval ?? new \DateInterval(self::DEFAULT_MAX_BACKOFF_PAUSE_DURATION)
        );
    }

    public function doBackoffPause(): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        sleep($this->currentBackoffPauseInSeconds);

        $this->currentBackoffPauseInSeconds = $this->currentBackoffPauseInSeconds < $this->maxBackoffPauseInSeconds ?
            $this->currentBackoffPauseInSeconds + $this->currentBackoffPauseInSeconds :
            $this->maxBackoffPauseInSeconds;
    }

    public function doPause(int $seconds = 1): void
    {
        $seconds = $seconds > 0 ? $seconds : 1;
        sleep($seconds);
    }

    public function resetBackoffPause(): void
    {
        $this->currentBackoffPauseInSeconds = 1;
    }

    protected function dateIntervalToSeconds(\DateInterval $dateInterval): int
    {
        $reference = new \DateTimeImmutable();
        $endTime = $reference->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

    /**
     * @return int
     */
    public function getCurrentBackoffPauseInSeconds(): int
    {
        return $this->currentBackoffPauseInSeconds;
    }
}
