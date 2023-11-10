<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Providers\Identity;

use SimpleSAML\Module\profilepage\Entities\Providers\Identity\Oidc;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Exceptions\MetadataException;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Providers\Identity\Oidc
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\profilepage\Entities\Providers\Bases\AbstractOidcProvider
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Oidc
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

    public function testCanGetProtocol(): void
    {
        $this->assertInstanceOf(
            \SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Oidc::class,
            (new Oidc($this->metadata))->getProtocol()
        );
    }
}
