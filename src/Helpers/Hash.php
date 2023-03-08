<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

class Hash
{
    protected Arr $arrayHelper;

    public function __construct(Arr $arrayHelper)
    {
        $this->arrayHelper = $arrayHelper;
    }

    public function getSha256(string $data): string
    {
        return hash('sha256', $data);
    }

    public function getSha256ForArray(array $array): string
    {
        $this->arrayHelper->recursivelySortByKey($array);
        return $this->getSha256(serialize($array));
    }
}
