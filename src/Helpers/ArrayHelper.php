<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

class ArrayHelper
{
    public function recursivelySortByKey(array &$array): void
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursivelySortByKey($value);
            }
        }

        ksort($array);
    }
}
