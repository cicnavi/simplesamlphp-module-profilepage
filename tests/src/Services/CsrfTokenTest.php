<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Services\CsrfToken;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Session;

/**
 * @covers \SimpleSAML\Module\accounting\Services\CsrfToken
 */
class CsrfTokenTest extends TestCase
{
    protected MockObject $sessionMock;
    protected Stub $helpersManagerStub;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(Session::class);
        $this->helpersManagerStub = $this->createStub(HelpersManager::class);
    }

    /**
     * @throws Exception
     */
    protected function prepareMockedInstance(): CsrfToken
    {
        return new CsrfToken($this->sessionMock, $this->helpersManagerStub);
    }

    /**
     * @throws Exception
     */
    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(CsrfToken::class, $this->prepareMockedInstance());
    }

    /**
     * @throws Exception
     */
    public function testCanGet(): void
    {
        $this->sessionMock->method('getData')->willReturn('sample');

        $this->assertSame($this->prepareMockedInstance()->get(), 'sample');
    }

    /**
     * @throws Exception
     */
    public function testCanValidate(): void
    {
        $this->sessionMock->method('getData')->willReturn('sample');

        $this->assertTrue($this->prepareMockedInstance()->validate('sample'));
        $this->assertFalse($this->prepareMockedInstance()->validate('invalid'));
    }
}
