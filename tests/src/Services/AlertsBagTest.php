<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\Services\AlertsBag;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Session;

/**
 * @covers \SimpleSAML\Module\profilepage\Services\AlertsBag
 */
class AlertsBagTest extends TestCase
{
    protected Stub $alertStub;
    protected MockObject $sessionMock;

    protected function setUp(): void
    {
        $this->alertStub = $this->createStub(AlertsBag\Alert::class);
        $this->sessionMock = $this->createMock(Session::class);
    }

    public function testCanConstruct(): void
    {
        $this->assertInstanceOf(AlertsBag::class, new AlertsBag($this->sessionMock));
    }

    /**
     * @throws Exception
     */
    public function testIsEmpty(): void
    {
        $this->sessionMock->method('getData')->willReturn([]);
        $this->sessionMock->expects($this->never())->method('setData');

        $this->assertFalse((new AlertsBag($this->sessionMock))->isNotEmpty());
    }

    /**
     * @throws Exception
     */
    public function testIsNotEmpty(): void
    {
        $this->sessionMock->method('getData')->willReturn([$this->alertStub]);
        $this->sessionMock->expects($this->never())->method('setData');

        $this->assertTrue((new AlertsBag($this->sessionMock))->isNotEmpty());
    }

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $alerts = [$this->alertStub];
        $this->sessionMock->method('getData')->willReturn($alerts);
        $this->sessionMock->expects($this->once())->method('setData');

        $this->assertSame($alerts, (new AlertsBag($this->sessionMock))->getAll());
    }

    public function testGetAllThrowsForInvalidData(): void
    {
        $this->sessionMock->method('getData')->willReturn('invalid');
        $this->sessionMock->expects($this->never())->method('setData');

        $this->expectException(Exception::class);
        (new AlertsBag($this->sessionMock))->getAll();
    }

    /**
     * @throws Exception
     */
    public function testPutCallsSetDataOnSession(): void
    {
        $this->sessionMock->expects($this->once())->method('setData');
        (new AlertsBag($this->sessionMock))->put($this->alertStub);
    }
}
