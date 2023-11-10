<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Interfaces;

use DateTimeImmutable;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;

interface JobInterface
{
    public function getId(): ?int;

    public function getRawState(): array;

    public function getType(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getAuthenticationEvent(): Event;
}
