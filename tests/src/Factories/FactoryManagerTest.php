<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Factories;

use SimpleSAML\Module\profilepage\Factories\FactoryManager;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Factories\MenuManagerFactory;
use SimpleSAML\Module\profilepage\Factories\UserFactory;

/**
 * @covers \SimpleSAML\Module\profilepage\Factories\FactoryManager
 * @uses \SimpleSAML\Module\profilepage\Factories\UserFactory
 * @uses \SimpleSAML\Module\profilepage\Factories\MenuManagerFactory
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
