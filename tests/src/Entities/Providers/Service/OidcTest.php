<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Providers\Service;

use SimpleSAML\Module\profilepage\Entities\Providers\Service\Oidc;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Exceptions\MetadataException;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Providers\Service\Oidc
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
            Oidc::METADATA_KEY_ENTITY_ID => 'sample-rp',
            Oidc::METADATA_KEY_NAME => 'Sample Name',
            Oidc::METADATA_KEY_DESCRIPTION => 'Sample description.',
        ];
    }

    public function testCanCreateInstance(): void
    {
        $serviceProvider = new Oidc($this->metadata);
        $this->assertSame($this->metadata, $serviceProvider->getMetadata());
        $this->assertSame($this->metadata[Oidc::METADATA_KEY_ENTITY_ID], $serviceProvider->getEntityId());
        $this->assertSame($this->metadata[Oidc::METADATA_KEY_NAME], $serviceProvider->getName());
        $this->assertSame($this->metadata[Oidc::METADATA_KEY_DESCRIPTION], $serviceProvider->getDescription());
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
