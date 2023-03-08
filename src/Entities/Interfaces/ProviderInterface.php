<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Interfaces;

use DateTimeImmutable;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;

interface ProviderInterface
{
    public function getMetadata(): array;
    public function getName(string $locale = 'en'): ?string;
    public function getEntityId(): string;
    public function getDescription(string $locale = 'en'): ?string;
}
