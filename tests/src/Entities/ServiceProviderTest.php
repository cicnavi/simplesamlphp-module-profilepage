<?php

namespace SimpleSAML\Test\Module\accounting\Entities;

use SimpleSAML\Module\accounting\Entities\ServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\ServiceProvider
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractEntityProvider
 */
class ServiceProviderTest extends TestCase
{

    /**
     * @var string[]
     */
    protected array $metadata;

    public function setUp(): void
    {
        $this->metadata = [
            'entityid' => 'http//example.org'
        ];
    }

    public function testCanCreateInstance(): void
    {
        $serviceProvider = new ServiceProvider($this->metadata);
        $this->assertSame($this->metadata, $serviceProvider->getMetadata());
    }
}
