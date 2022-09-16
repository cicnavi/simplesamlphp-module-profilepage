<?php

namespace SimpleSAML\Module\accounting\Entities\Activity;

use SimpleSAML\Module\accounting\Entities\Activity;

class Bag
{
    protected array $activities = [];

    public function add(Activity $activity): void
    {
        $this->activities[] = $activity;
    }
}
