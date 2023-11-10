<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities;

use SimpleSAML\Module\profilepage\Entities\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\User
 */
class UserTest extends TestCase
{
    /**
     * @var string[][]
     */
    protected array $attributes;

    protected function setUp(): void
    {
        $this->attributes = [
            'uid' => ['test'],
        ];
    }

    public function testCanCreateInstance(): void
    {
        $user = new User($this->attributes);
        $this->assertSame($this->attributes, $user->getAttributes());
    }
}
