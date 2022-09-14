<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities;

class IdentityProvider
{
    protected array $metadata;

    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
