<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Factories;

use SimpleSAML\Module\profilepage\Factories\MenuManagerFactory;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Services\MenuManager;

/**
 * @covers \SimpleSAML\Module\profilepage\Factories\MenuManagerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\MenuManager
 */
class MenuManagerFactoryTest extends TestCase
{
    protected function mocked(): MenuManagerFactory
    {
        return new MenuManagerFactory();
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(MenuManager::class, $this->mocked()->build());
    }
}
