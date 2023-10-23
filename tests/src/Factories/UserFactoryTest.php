<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Factories;

use SimpleSAML\Module\accounting\Entities\User;
use SimpleSAML\Module\accounting\Factories\UserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Factories\UserFactory
 * @uses \SimpleSAML\Module\accounting\Entities\User
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
