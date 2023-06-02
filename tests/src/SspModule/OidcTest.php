<?php

namespace SimpleSAML\Test\Module\accounting\SspModule;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\Database;
use SimpleSAML\Module\accounting\Helpers\DateTime;
use SimpleSAML\Module\accounting\Helpers\SspModule;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\SspModule\Oidc;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\oidc\Services\Container;

/**
 * @covers \SimpleSAML\Module\accounting\SspModule\Oidc
 */
class OidcTest extends TestCase
{
    protected MockObject $loggerMock;
    protected MockObject $helpersManagerMock;
    protected MockObject $containerMock;
    protected MockObject $databaseMock;
    protected MockObject $sspModuleMock;
    protected MockObject $dateTimeMock;
    protected MockObject $pdoStatementMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helpersManagerMock = $this->createMock(HelpersManager::class);
        $this->containerMock = $this->createMock(Container::class);
        $this->databaseMock = $this->createMock(Database::class);

        $this->sspModuleMock = $this->createMock(SspModule::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->pdoStatementMock = $this->createMock(\PDOStatement::class);
    }

    protected function prepareMockedInstance(): Oidc
    {
        $this->helpersManagerMock->method('getSspModule')->willReturn($this->sspModuleMock);
        $this->helpersManagerMock->method('getDateTime')->willReturn($this->dateTimeMock);

        return new Oidc(
            $this->loggerMock,
            $this->helpersManagerMock,
            $this->containerMock,
            $this->databaseMock
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Oidc::class,
            $this->prepareMockedInstance()
        );
    }

    public function testIsEnabled(): void
    {
        $this->sspModuleMock->method('isEnabled')->willReturn(true);
        $this->assertTrue($this->prepareMockedInstance()->isEnabled());
    }

    public function testIsNotEnabled(): void
    {
        $this->sspModuleMock->method('isEnabled')->willReturn(false);
        $this->assertFalse($this->prepareMockedInstance()->isEnabled());
    }

    public function testCanGetContainer(): void
    {
        $this->assertInstanceOf(
            Container::class,
            $this->prepareMockedInstance()->getContainer()
        );
    }

    public function testGetUsersAccessTokens(): void
    {
        $this->pdoStatementMock->method('fetchAll')->willReturn(['sample']);
        $this->databaseMock->method('read')->willReturn($this->pdoStatementMock);

        $this->assertNotEmpty(
            $this->prepareMockedInstance()->getUsersAccessTokens('userId', ['clientId'])
        );
    }

    public function testGetUsersAccessTokensEmpty(): void
    {
        $this->databaseMock->expects($this->once())->method('read');
        $this->assertEmpty(
            $this->prepareMockedInstance()->getUsersAccessTokens('userId', ['clientId'])
        );
    }

    public function testRevokeUsersAccessToken(): void
    {
        $this->databaseMock->expects($this->once())->method('write');
        $this->prepareMockedInstance()->revokeUsersAccessToken('userId', 'accessTokenId');
    }

    public function testGetUsersRefreshTokens(): void
    {
        $this->pdoStatementMock->method('fetchAll')->willReturn(['sample']);
        $this->databaseMock->method('read')->willReturn($this->pdoStatementMock);

        $this->assertNotEmpty(
            $this->prepareMockedInstance()->getUsersRefreshTokens('userId', ['clientId'])
        );
    }

    public function testGetUsersRefreshTokensEmpty(): void
    {
        $this->databaseMock->expects($this->once())->method('read');
        $this->assertEmpty(
            $this->prepareMockedInstance()->getUsersRefreshTokens('userId', ['clientId'])
        );
    }

    public function testRevokeUsersRefreshToken(): void
    {
        $this->databaseMock->expects($this->once())->method('write');
        $this->prepareMockedInstance()->revokeUsersRefreshToken('userId', 'refreshTokenId');
    }

    public function testGetClients(): void
    {
        $this->pdoStatementMock->method('fetchAll')->willReturn(['sample']);
        $this->databaseMock->method('read')->willReturn($this->pdoStatementMock);

        $this->assertNotEmpty(
            $this->prepareMockedInstance()->getClients(['clientId'])
        );
    }

    public function testGetClientsEmpty(): void
    {
        $this->databaseMock->expects($this->once())->method('read');
        $this->assertEmpty(
            $this->prepareMockedInstance()->getClients(['clientId'])
        );
    }
}
