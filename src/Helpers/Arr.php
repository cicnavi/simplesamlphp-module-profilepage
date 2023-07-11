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

    /**
     * @param array $array
     * @param  string|int  ...$keys
     * @return array|null
     */
    public function getNestedElementByKey(array $array, ...$keys): ?array
    {
        $element = $array;

        foreach ($keys as $key) {
            if (!is_array($element)) {
                return null;
            }

            if (!isset($element[$key])) {
                return null;
            }

            /** @var mixed $element */
            $element = $element[$key];
        }

        if (is_array($element)) {
            return $element;
        }

        return [$element];
    }
}
