<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Http\Controllers\User;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use SimpleSAML\Module\profilepage\Data\Providers\Builders\DataProviderBuilder;
use SimpleSAML\Module\profilepage\Entities\User;
use SimpleSAML\Module\profilepage\Factories\FactoryManager;
use SimpleSAML\Module\profilepage\Factories\MenuManagerFactory;
use SimpleSAML\Module\profilepage\Factories\UserFactory;
use SimpleSAML\Module\profilepage\Http\Controllers\User\Profile;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\AlertsBag;
use SimpleSAML\Module\profilepage\Services\CsrfToken;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\MenuManager;
use SimpleSAML\Module\profilepage\Services\SspModuleManager;
use SimpleSAML\Session;

/**
 * @covers \SimpleSAML\Module\profilepage\Http\Controllers\User\Profile
 */
class ProfileTest extends TestCase
{
    private MockObject $moduleConfigurationMock;
    private MockObject $sspConfigurationMock;
    private MockObject $seessionMock;
    private MockObject $loggerMock;
    private MockObject $authSimpleMock;
    private MockObject $dataProviderBuilderMock;
    private MockObject $helpersManagerMock;
    private MockObject $sspModuleManagerMock;
    private MockObject $csrfTokenMock;
    private MockObject $alertsBagMock;
    private MockObject $factoryManagerMock;
    private MockObject $userMock;
    private MockObject $userFactoryMock;
    private MockObject $menuManagerMock;
    private MockObject $menuManagerFactoryMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationMock = $this->createMock(ModuleConfiguration::class);
        $this->sspConfigurationMock = $this->createMock(Configuration::class);
        $this->seessionMock = $this->createMock(Session::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->authSimpleMock = $this->createMock(Simple::class);
        $this->dataProviderBuilderMock = $this->createMock(DataProviderBuilder::class);
        $this->helpersManagerMock = $this->createMock(HelpersManager::class);
        $this->sspModuleManagerMock = $this->createMock(SspModuleManager::class);
        $this->csrfTokenMock = $this->createMock(CsrfToken::class);
        $this->alertsBagMock = $this->createMock(AlertsBag::class);
        $this->factoryManagerMock = $this->createMock(FactoryManager::class);

        $this->userMock = $this->createMock(User::class);
        $this->userFactoryMock = $this->createMock(UserFactory::class);
        $this->userFactoryMock->method('build')->willReturn($this->userMock);
        $this->factoryManagerMock->method('userFactory')->willReturn($this->userFactoryMock);

        $this->menuManagerMock = $this->createMock(MenuManager::class);
        $this->menuManagerFactoryMock = $this->createMock(MenuManagerFactory::class);
        $this->menuManagerFactoryMock->method('build')->willReturn($this->menuManagerMock);
        $this->factoryManagerMock->method('menuManagerFactory')->willReturn($this->menuManagerFactoryMock);
    }

    /**
     * @throws Exception
     */
    protected function mocked(): Profile
    {
        return new Profile(
            $this->moduleConfigurationMock,
            $this->sspConfigurationMock,
            $this->seessionMock,
            $this->loggerMock,
            $this->authSimpleMock,
            $this->dataProviderBuilderMock,
            $this->helpersManagerMock,
            $this->sspModuleManagerMock,
            $this->csrfTokenMock,
            $this->alertsBagMock,
            $this->factoryManagerMock,
        );
    }

    /**
     * @throws Exception
     */
    public function testCanMockInstance(): void
    {
        $this->assertInstanceOf(Profile::class, $this->mocked());
    }
}
