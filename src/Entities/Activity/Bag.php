<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Activity;

use SimpleSAML\Module\accounting\Entities\Activity;

class Bag
{
    /**
     * @var Activity[]
     */
    protected array $activities = [];

    public function add(Activity $activity): void
    {
        $this->activities[] = $activity;
    }

    /**
     * @return Activity[]
     */
    public function getAll(): array
    {
        return $this->activities;
    }
}
