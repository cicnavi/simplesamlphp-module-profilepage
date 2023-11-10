<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Factories;

use SimpleSAML\Module\profilepage\Entities\User;

class UserFactory
{
    public function build(array $attributes): User
    {
        return new User($attributes);
    }
}
