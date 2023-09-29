<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Bases;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\StateException;
use SimpleSAML\Module\accounting\Services\HelpersManager;

abstract class AbstractJob implements JobInterface
{
    protected DateTimeImmutable $createdAt;
    protected HelpersManager $helpersManager;

    public function __construct(
        protected array $rawState,
        protected ?int $id = null,
        DateTimeImmutable $createdAt = null,
        HelpersManager $helpersManager = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->helpersManager = $helpersManager ?? new HelpersManager();

        $this->normalizeRawState();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRawState(): array
    {
        return $this->rawState;
    }

    public function setRawState(array $rawState): void
    {
        $this->rawState = $rawState;
    }

    /**
     * @throws StateException
     */
    public function getAuthenticationEvent(): Event
    {
        $state = $this->helpersManager->getAuthenticationEventStateResolver()->fromStateArray($this->rawState);
        return new Event(
            $state,
            $state->getAuthenticationInstant() ?? $this->createdAt
        );
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    abstract public function getType(): string;

    protected function normalizeRawState(): void
    {
        // If we don't have AuthnInstant set in state, use the job creation time, so that this gets stored...
        if (empty($this->rawState[AbstractState::KEY_AUTHENTICATION_INSTANT])) {
            $this->rawState[AbstractState::KEY_AUTHENTICATION_INSTANT] = $this->createdAt->getTimestamp();
        }
    }
}
