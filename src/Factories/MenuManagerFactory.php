<?php

namespace SimpleSAML\Module\accounting\Factories;

use SimpleSAML\Module\accounting\Services\MenuManager;

class MenuManagerFactory
{
    public function build(): MenuManager
    {
        return new MenuManager();
    }
}
