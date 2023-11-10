<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Factories;

class FactoryManager
{
    protected static ?UserFactory $userFactory = null;
    protected static ?MenuManagerFactory $menuManagerFactory = null;

    public function userFactory(): UserFactory
    {
        return self::$userFactory ??= new UserFactory();
    }

    public function menuManagerFactory(): MenuManagerFactory
    {
        return self::$menuManagerFactory ??= new MenuManagerFactory();
    }
}
