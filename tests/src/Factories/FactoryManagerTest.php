<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Factories;

use SimpleSAML\Module\accounting\Factories\FactoryManager;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Factories\MenuManagerFactory;
use SimpleSAML\Module\accounting\Factories\UserFactory;

/**
 * @covers \SimpleSAML\Module\accounting\Factories\FactoryManager
 * @uses \SimpleSAML\Module\accounting\Factories\UserFactory
 * @uses \SimpleSAML\Module\accounting\Factories\MenuManagerFactory
 */
class FactoryManagerTest extends TestCase
{
    protected function mocked(): FactoryManager
    {
        return new FactoryManager();
    }
    public function testCanManageFactories(): void
    {
        $this->assertInstanceOf(UserFactory::class, $this->mocked()->userFactory());
        $this->assertInstanceOf(MenuManagerFactory::class, $this->mocked()->menuManagerFactory());
    }
}
