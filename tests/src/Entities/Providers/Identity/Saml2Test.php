<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Providers\Identity;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Providers\Identity\Saml2;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Providers\Identity\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
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
            Saml2::METADATA_KEY_ENTITY_ID => 'http//example.org/idp',
            Saml2::METADATA_KEY_NAME => 'Sample IdP',
            Saml2::METADATA_KEY_DESCRIPTION => 'Sample description.',
        ];
    }

    public function testCanCreateInstance(): void
    {
        $identityProvider = new Saml2($this->metadata);
        $this->assertSame($this->metadata, $identityProvider->getMetadata());
        $this->assertSame($this->metadata[Saml2::METADATA_KEY_NAME], $identityProvider->getName());
        $this->assertSame($this->metadata[Saml2::METADATA_KEY_DESCRIPTION], $identityProvider->getDescription());
    }

    public function testThrowsIfEntityIdNotSet(): void
    {
        $metadata = $this->metadata;
        unset($metadata[Saml2::METADATA_KEY_ENTITY_ID]);

        $this->expectException(MetadataException::class);

        new Saml2($metadata);
    }
}
