<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Factories;

use SimpleSAML\Module\profilepage\Entities\User;
use SimpleSAML\Module\profilepage\Factories\UserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Factories\UserFactory
 * @uses \SimpleSAML\Module\profilepage\Entities\User
 */
class UserFactoryTest extends TestCase
{
    protected function mocked(): UserFactory
    {
        return new UserFactory();
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(User::class, $this->mocked()->build(['test' => 'test']));
    }
}
