<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Bases;

abstract class AbstractPayload
{
    /**
     * @return mixed
     */
    abstract public function getRawPayloadData();
}
