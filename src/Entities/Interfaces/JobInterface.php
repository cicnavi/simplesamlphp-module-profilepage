<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Interfaces;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;

interface JobInterface
{
    public function getId(): ?int;

    public function getPayload(): AbstractPayload;
    public function setPayload(AbstractPayload $payload): void;

    public function getType(): string;

    public function getCreatedAt(): DateTimeImmutable;
}
