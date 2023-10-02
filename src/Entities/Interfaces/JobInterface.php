<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Interfaces;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;

interface JobInterface
{
    public function getId(): ?int;

    public function getRawState(): array;

    public function getType(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getAuthenticationEvent(): Event;
}
