<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Providers\Service;

use SimpleSAML\Module\profilepage\Entities\Providers\Bases\AbstractSaml2Provider;
use SimpleSAML\Module\profilepage\Entities\Providers\Service\Saml2;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Exceptions\MetadataException;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Providers\Service\Saml2
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\profilepage\Entities\Providers\Bases\AbstractSaml2Provider
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2
 */
class Saml2Test extends TestCase
{
    protected array $metadata;

    protected function setUp(): void
    {
        $this->metadata = [
            AbstractSaml2Provider::METADATA_KEY_ENTITY_ID => 'sample-serivice',
            AbstractSaml2Provider::METADATA_KEY_NAME => 'Sample Name',
            AbstractSaml2Provider::METADATA_KEY_DESCRIPTION => 'Sample description.',
        ];
    }

    public function testCanCreateInstance(): void
    {
        $serviceProvider = new Saml2($this->metadata);
        $this->assertSame($this->metadata, $serviceProvider->getMetadata());
        $this->assertSame(
            $this->metadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID],
            $serviceProvider->getEntityId()
        );
        $this->assertSame($this->metadata[AbstractSaml2Provider::METADATA_KEY_NAME], $serviceProvider->getName());
        $this->assertSame(
            $this->metadata[AbstractSaml2Provider::METADATA_KEY_DESCRIPTION],
            $serviceProvider->getDescription()
        );
    }

    public function testThrowsIfEntityIdNotSet(): void
    {
        $metadata = $this->metadata;
        unset($metadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID]);

        $this->expectException(MetadataException::class);

        new Saml2($metadata);
    }

    public function testCanGetProtocol(): void
    {
        $this->assertInstanceOf(
            \SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2::class,
            (new Saml2($this->metadata))->getProtocol()
        );
    }
}
