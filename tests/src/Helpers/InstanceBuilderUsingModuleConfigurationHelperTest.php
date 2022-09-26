<?php

namespace SimpleSAML\Test\Module\accounting\Helpers;

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
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationstub;
    protected \PHPUnit\Framework\MockObject\Stub $loggerStub;

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

        $this->moduleConfigurationstub = $this->createStub(ModuleConfiguration::class);
        $this->loggerStub = $this->createStub(LoggerInterface::class);
    }

    public function testCanBuildClassInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            BuildableUsingModuleConfigurationInterface::class,
            InstanceBuilderUsingModuleConfigurationHelper::build(
                $this->stubClass,
                $this->moduleConfigurationstub,
                $this->loggerStub
            )
        );
    }

    public function testThrowsForInvalidClass(): void
    {
        $this->expectException(Exception::class);

        /** @psalm-suppress InvalidArgument */
        InstanceBuilderUsingModuleConfigurationHelper::build(
            ModuleConfiguration::class, // Sample class which is not buildable.
            $this->moduleConfigurationstub,
            $this->loggerStub
        );
    }
}
