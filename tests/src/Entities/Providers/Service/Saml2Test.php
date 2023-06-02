<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Providers\Service;

use SimpleSAML\Module\accounting\Entities\Providers\Service\Saml2;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Providers\Service\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 */
class Saml2Test extends TestCase
{
    protected array $metadata;

    protected function setUp(): void
    {
        $this->metadata = [
            Saml2::METADATA_KEY_ENTITY_ID => 'sample-serivice',
            Saml2::METADATA_KEY_NAME => 'Sample Name',
            Saml2::METADATA_KEY_DESCRIPTION => 'Sample description.',
        ];
    }

    public function testCanCreateInstance(): void
    {
        $serviceProvider = new Saml2($this->metadata);
        $this->assertSame($this->metadata, $serviceProvider->getMetadata());
        $this->assertSame($this->metadata[Saml2::METADATA_KEY_ENTITY_ID], $serviceProvider->getEntityId());
        $this->assertSame($this->metadata[Saml2::METADATA_KEY_NAME], $serviceProvider->getName());
        $this->assertSame($this->metadata[Saml2::METADATA_KEY_DESCRIPTION], $serviceProvider->getDescription());
    }

    public function testThrowsIfEntityIdNotSet(): void
    {
        $metadata = $this->metadata;
        unset($metadata[Saml2::METADATA_KEY_ENTITY_ID]);

        $this->expectException(MetadataException::class);

        new Saml2($metadata);
    }

    public function testCanGetProtocol(): void
    {
        $this->assertInstanceOf(
            \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2::class,
            (new Saml2($this->metadata))->getProtocol()
        );
    }
}
