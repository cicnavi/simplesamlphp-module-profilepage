<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services\JobRunner;

use DateInterval;
use SimpleSAML\Module\accounting\Services\HelpersManager;

class RateLimiter
{
    public const DEFAULT_MAX_PAUSE_DURATION = 'PT10M';
    public const DEFAULT_MAX_BACKOFF_PAUSE_DURATION = 'PT1M';

    protected HelpersManager $helpersManager;

    protected int $maxPauseInSeconds;
    protected int $maxBackoffPauseInSeconds;
    protected int $currentBackoffPauseInSeconds = 1;

    public function __construct(
        DateInterval $maxPauseInterval = null,
        DateInterval $maxBackoffInterval = null,
        HelpersManager $helpersManager = null
    ) {
        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->maxPauseInSeconds = $this->helpersManager->getDateTime()->convertDateIntervalToSeconds(
            $maxPauseInterval ?? new DateInterval(self::DEFAULT_MAX_PAUSE_DURATION)
        );
        $this->maxBackoffPauseInSeconds = $this->helpersManager->getDateTime()->convertDateIntervalToSeconds(
            $maxBackoffInterval ?? new DateInterval(self::DEFAULT_MAX_BACKOFF_PAUSE_DURATION)
        );
    }

    public function doBackoffPause(): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        sleep($this->currentBackoffPauseInSeconds);

        $newBackoffPauseInSeconds = $this->currentBackoffPauseInSeconds + $this->currentBackoffPauseInSeconds;
        $this->currentBackoffPauseInSeconds = min($newBackoffPauseInSeconds, $this->maxBackoffPauseInSeconds);
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

    /**
     * @return int
     */
    public function getMaxPauseInSeconds(): int
    {
        return $this->maxPauseInSeconds;
    }

    /**
     * @return int
     */
    public function getMaxBackoffPauseInSeconds(): int
    {
        return $this->maxBackoffPauseInSeconds;
    }

    /**
     * @return int
     */
    public function getCurrentBackoffPauseInSeconds(): int
    {
        return $this->currentBackoffPauseInSeconds;
    }
}
