<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

class Arr
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

    /**
     * @param array $array
     * @param int|string $key
     * @return array
     */
    public function groupByValue(array $array, $key): array
    {
        return array_reduce($array, function (array $carry, array $item) use ($key) {
            /** @psalm-suppress MixedArrayOffset, MixedArrayAssignment */
            $carry[$item[$key]][] = $item;
            return $carry;
        }, []);
    }

    public function isAssociative(array $array): bool
    {
        $keys = array_keys($array);
        return $keys !== array_keys($keys);
    }
}
