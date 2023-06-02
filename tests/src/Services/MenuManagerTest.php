<?php

namespace SimpleSAML\Test\Module\accounting\Services;

use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Services\MenuManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\MenuManager
 */
class MenuManagerTest extends TestCase
{
    protected Stub $menuItemStub;

    protected function setUp(): void
    {
        $this->menuItemStub = $this->createStub(MenuManager\MenuItem::class);
    }

    public function testCanWorkWithItems(): void
    {
        $menuManager = new MenuManager();

        $this->assertEmpty($menuManager->getItems());
        $menuManager->addItem($this->menuItemStub);
        $this->assertNotEmpty($menuManager->getItems());
        $this->assertCount(1, $menuManager->getItems());
        $menuManager->addItems($this->menuItemStub, $this->menuItemStub);
        $this->assertCount(3, $menuManager->getItems());
    }
}
