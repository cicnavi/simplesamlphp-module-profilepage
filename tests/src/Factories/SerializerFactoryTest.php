<?php

namespace SimpleSAML\Test\Module\profilepage\Factories;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Module\profilepage\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\profilepage\Factories\SerializerFactory;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;

/**
 * @covers \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 */
class SerializerFactoryTest extends TestCase
{
    private MockObject $moduleConfigurationMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
    }

    protected function mocked(): SerializerFactory
    {
        return new SerializerFactory($this->moduleConfigurationMock);
    }

    public function testCanBuildSerializer(): void
    {
        $this->moduleConfigurationMock->method('getSerializerClass')->willReturn(PhpSerializer::class);
        $this->assertInstanceOf(SerializerInterface::class, $this->mocked()->build());
    }

    public function testThrowsOnInvalidSerializerClass(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->moduleConfigurationMock->method('getSerializerClass')->willReturn((new \stdClass())::class);

        $this->mocked()->build();
    }

}
