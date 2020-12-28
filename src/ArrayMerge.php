<?php

declare(strict_types=1);

namespace YamlMigrate;

class ArrayMerge
{
    public static function merge(array $arr1, array $arr2): array
    {
        return self::mergeDeepArray([$arr1, $arr2]);
    }

    /**
     * @see https://api.drupal.org/api/drupal/vendor%21wikimedia%21composer-merge-plugin%21src%21Merge%21NestedArray.php/function/NestedArray%3A%3AmergeDeepArray/8.7.x
     */
    public static function mergeDeepArray(array $arrays, $preserveIntegerKeys = false): array
    {
        $result = [];
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                // Don't add duplicates of objects here
                if (\in_array($value, $result, true) && \is_object($value)) {
                    continue;
                }
                // Renumber integer keys as array_merge_recursive() does
                // unless $preserveIntegerKeys is set to TRUE. Note that PHP
                // automatically converts array keys that are integer strings
                // (e.g., '1') to integers.
                if (\is_int($key) && ! $preserveIntegerKeys) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && \is_array($result[$key]) && \is_array($value)) {
                    // Recurse when both values are arrays.
                    $result[$key] = self::mergeDeepArray([
                        $result[$key],
                        $value,
                    ], $preserveIntegerKeys);
                } else {
                    // Otherwise, use the latter value, overriding any
                    // previous value.
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
