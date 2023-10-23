<?php

namespace SimpleSAML\Module\accounting\Factories;

use SimpleSAML\Module\accounting\Entities\User;

class UserFactory
{
    public function build(array $attributes): User
    {
        return new User($attributes);
    }
}
