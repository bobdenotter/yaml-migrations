<?php

declare(strict_types=1);

namespace YamlMigrate;

class ArrayMerge
{
    public static function merge(array $arr1, array $arr2): array
    {
        $result = array_merge_recursive($arr1, $arr2);

        return self::deepUnique($result);
    }

    private static function deepUnique(array $data): array
    {
        $result = array_map('unserialize', array_unique(array_map('serialize', $data)));

        foreach ($result as $key => $value) {
            if (\is_array($value)) {
                $result[$key] = self::deepUnique($value);
            }
        }

        return $result;
    }
}
