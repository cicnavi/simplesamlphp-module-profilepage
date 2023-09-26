<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Providers\Identity;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractSaml2Provider;
use SimpleSAML\Module\accounting\Entities\Providers\Identity\Saml2;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Providers\Identity\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractSaml2Provider
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2
 */
class Saml2Test extends TestCase
{
    /**
     * @var string[]
     */
    protected array $metadata;

    public function setUp(): void
    {
        $this->metadata = [
            AbstractSaml2Provider::METADATA_KEY_ENTITY_ID => 'http//example.org/idp',
            AbstractSaml2Provider::METADATA_KEY_NAME => 'Sample IdP',
            AbstractSaml2Provider::METADATA_KEY_DESCRIPTION => 'Sample description.',
        ];
    }

    public function testCanCreateInstance(): void
    {
        $identityProvider = new Saml2($this->metadata);
        $this->assertSame($this->metadata, $identityProvider->getMetadata());
        $this->assertSame($this->metadata[AbstractSaml2Provider::METADATA_KEY_NAME], $identityProvider->getName());
        $this->assertSame(
            $this->metadata[AbstractSaml2Provider::METADATA_KEY_DESCRIPTION],
            $identityProvider->getDescription()
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
            \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2::class,
            (new Saml2($this->metadata))->getProtocol()
        );
    }
}
