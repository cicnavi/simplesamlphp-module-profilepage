<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Factories;

use SimpleSAML\Module\accounting\Factories\MenuManagerFactory;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Services\MenuManager;

/**
 * @covers \SimpleSAML\Module\accounting\Factories\MenuManagerFactory
 * @uses \SimpleSAML\Module\accounting\Services\MenuManager
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
