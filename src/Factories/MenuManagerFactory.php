<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Factories;

use SimpleSAML\Module\profilepage\Services\MenuManager;

class MenuManagerFactory
{
    public function build(): MenuManager
    {
        return new MenuManager();
    }
}
