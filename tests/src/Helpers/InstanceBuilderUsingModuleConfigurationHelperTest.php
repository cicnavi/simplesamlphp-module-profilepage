<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper
 */
class InstanceBuilderUsingModuleConfigurationHelperTest extends TestCase
{
    protected BuildableUsingModuleConfigurationInterface $stub;
    /** @var class-string */
    protected string $stubClass;
    protected Stub $moduleConfigurationStub;
    protected Stub $loggerStub;

    protected function setUp(): void
    {
        $this->stub = new class () implements BuildableUsingModuleConfigurationInterface {
            public static function build(
                ModuleConfiguration $moduleConfiguration,
                LoggerInterface $logger
            ): BuildableUsingModuleConfigurationInterface {
                return new self();
            }
        };

        $this->stubClass = get_class($this->stub);

        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->loggerStub = $this->createStub(LoggerInterface::class);
    }

    /**
     * @throws Exception
     */
    public function testCanBuildClassInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            BuildableUsingModuleConfigurationInterface::class,
            (new InstanceBuilderUsingModuleConfigurationHelper())->build(
                $this->stubClass,
                $this->moduleConfigurationStub,
                $this->loggerStub
            )
        );
    }

    public function testThrowsForInvalidClass(): void
    {
        $this->expectException(Exception::class);

        /** @psalm-suppress InvalidArgument */
        (new InstanceBuilderUsingModuleConfigurationHelper())->build(
            ModuleConfiguration::class, // Sample class which is not buildable.
            $this->moduleConfigurationStub,
            $this->loggerStub
        );
    }
}
