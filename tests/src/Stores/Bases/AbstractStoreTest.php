<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Bases;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Bases\AbstractStore;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Bases\AbstractStore
 */
class AbstractStoreTest extends TestCase
{
    /**
     * @var AbstractStore
     */
    protected $abstractStore;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ModuleConfiguration
     */
    protected $moduleConfigurationStub;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|LoggerInterface
     */
    protected $loggerStub;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->loggerStub = $this->createStub(LoggerInterface::class);

        $this->abstractStore = new class (
            $this->moduleConfigurationStub,
            $this->loggerStub
        ) extends AbstractStore {
            public static function build(
                ModuleConfiguration $moduleConfiguration,
                LoggerInterface $logger,
                string $connectionKey = null
            ): AbstractStore {
                return new self($moduleConfiguration, $logger, $connectionKey);
            }
            public function needsSetup(): bool
            {
                return false;
            }
            public function runSetup(): void
            {
            }
        };
    }

    public function testCanBuildInstance(): void
    {
        $this->assertInstanceOf(AbstractStore::class, $this->abstractStore);
        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            AbstractStore::class,
            $this->abstractStore::build($this->moduleConfigurationStub, $this->loggerStub)
        );
    }
}
