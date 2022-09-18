<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Bases;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;

abstract class AbstractJob implements JobInterface
{
    protected AbstractPayload $payload;
    protected ?int $id;
    protected DateTimeImmutable $createdAt;

    public function __construct(
        AbstractPayload $payload,
        int $id = null,
        DateTimeImmutable $createdAt = null
    ) {
        $this->setPayload($payload);
        $this->id = $id;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPayload(): AbstractPayload
    {
        return $this->payload;
    }

    public function setPayload(AbstractPayload $payload): void
    {
        $this->payload = $payload;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    abstract public function getType(): string;

    /**
     * @return mixed
     */
    abstract public function getRawPayloadData();
}
