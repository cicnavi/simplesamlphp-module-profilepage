<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Providers\Bases;

use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\ProviderInterface;
use SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractSaml2Provider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;
use SimpleSAML\Module\accounting\Helpers\Arr;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractSaml2Provider
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 */
class AbstractSaml2ProviderTest extends TestCase
{
    protected Stub $helpersManagerStub;
    protected Stub $arrStub;
    protected array $metadata;

    protected function setUp(): void
    {
        $this->helpersManagerStub = $this->createStub(HelpersManager::class);
        $this->arrStub = $this->createStub(Arr::class);
        $this->metadata = StateArrays::SAML2_FULL['SPMetadata'];
    }

    protected function prepareInstance(array $metadata): AbstractSaml2Provider
    {
        $this->helpersManagerStub->method('getArr')->willReturn($this->arrStub);

        return new class ($metadata, $this->helpersManagerStub) extends AbstractSaml2Provider {
            protected function getProviderDescription(): string
            {
                return 'service description';
            }
        };
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AbstractSaml2Provider::class, $this->prepareInstance($this->metadata));
    }

    public function testCanGetName(): void
    {
        $metadata = $this->metadata;
        $this->assertSame(
            $this->metadata[AbstractSaml2Provider::METADATA_KEY_NAME],
            $this->prepareInstance($metadata)->getName()
        );

        unset($metadata[AbstractSaml2Provider::METADATA_KEY_NAME]);
        $this->assertSame(
            $this->metadata[AbstractSaml2Provider::METADATA_KEY_UI_INFO]
                [AbstractSaml2Provider::METADATA_KEY_UI_INFO_DISPLAY_NAME]
                [ProviderInterface::DEFAULT_LOCALE],
            $this->prepareInstance($metadata)->getName()
        );

        unset($metadata[AbstractSaml2Provider::METADATA_KEY_UI_INFO]);
        $this->assertNull($this->prepareInstance($metadata)->getName());
    }

    public function testCanGetDescription(): void
    {
        $metadata = $this->metadata;
        $this->assertSame(
            $this->metadata[AbstractSaml2Provider::METADATA_KEY_DESCRIPTION],
            $this->prepareInstance($metadata)->getDescription()
        );

        unset($metadata[AbstractSaml2Provider::METADATA_KEY_DESCRIPTION]);
        $this->assertSame(
            $this->metadata[AbstractSaml2Provider::METADATA_KEY_UI_INFO]
            [AbstractSaml2Provider::METADATA_KEY_UI_INFO_DESCRIPTION]
            [ProviderInterface::DEFAULT_LOCALE],
            $this->prepareInstance($metadata)->getDescription()
        );

        unset($metadata[AbstractSaml2Provider::METADATA_KEY_UI_INFO]);
        $this->assertNull($this->prepareInstance($metadata)->getDescription());
    }

    public function testCanGetLogo(): void
    {
        $this->arrStub->method('getNestedElementByKey')->willReturn(['https://example.org/logo']);

        $this->assertSame(
            'https://example.org/logo',
            $this->prepareInstance($this->metadata)->getLogoUrl()
        );
    }

    public function testGetLogoNotFound(): void
    {
        $this->arrStub->method('getNestedElementByKey')->willReturn(null);

        $this->assertNull(
            $this->prepareInstance($this->metadata)->getLogoUrl()
        );
    }

    public function testGetLogoIsNullIfNotValid(): void
    {
        $this->arrStub->method('getNestedElementByKey')->willReturn(['not-valid']);

        $this->assertNull(
            $this->prepareInstance($this->metadata)->getLogoUrl()
        );
    }

    public function testThrowsForInvalidEntityId(): void
    {
        $metadata = $this->metadata;
        unset($metadata[AbstractSaml2Provider::METADATA_KEY_ENTITY_ID]);

        $this->expectException(MetadataException::class);

        $this->prepareInstance($metadata);
    }

    public function testCanGetProtocol(): void
    {
        $this->assertInstanceOf(
            AuthenticationProtocolInterface::class,
            $this->prepareInstance($this->metadata)->getProtocol()
        );
    }
}
