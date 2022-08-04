<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Interfaces;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;

interface JobInterface
{
    public function run(): void;

    public function getPayload(): AbstractPayload;

    public function setPayload(AbstractPayload $payload): void;
}
