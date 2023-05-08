<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Providers\Identity;

use SimpleSAML\Module\accounting\Entities\Providers\Identity\Oidc;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Providers\Identity\Oidc
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 */
class OidcTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $metadata;

    protected function setUp(): void
    {
        $this->metadata = [
            Oidc::METADATA_KEY_ENTITY_ID => 'sample-issuer',
        ];
    }

    public function testCanCreateInstance(): void
    {
        $identityProvider = new Oidc($this->metadata);
        $this->assertSame($identityProvider->getMetadata(), $this->metadata);
        $this->assertSame($identityProvider->getEntityId(), $this->metadata[Oidc::METADATA_KEY_ENTITY_ID]);
        $this->assertSame($identityProvider->getName(), null);
        $this->assertSame($identityProvider->getDescription(), null);
    }

    public function testThrowsIfEntityIdNotSet(): void
    {
        $metadata = $this->metadata;
        unset($metadata[Oidc::METADATA_KEY_ENTITY_ID]);

        $this->expectException(MetadataException::class);

        new Oidc($metadata);
    }
}