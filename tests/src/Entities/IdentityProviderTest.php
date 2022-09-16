<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractEntityProvider;
use SimpleSAML\Module\accounting\Entities\IdentityProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\IdentityProvider
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractEntityProvider
 */
class IdentityProviderTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $metadata;

    public function setUp(): void
    {
        $this->metadata = [
            AbstractEntityProvider::METADATA_KEY_ENTITY_ID => 'http//example.org/idp'
        ];
    }

    public function testCanCreateInstance(): void
    {
        $identityProvider = new IdentityProvider($this->metadata);
        $this->assertSame($this->metadata, $identityProvider->getMetadata());
    }
}
