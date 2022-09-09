<?php

/*
 * This file is part of the Sitegeist.Noderobis package.
 */

declare(strict_types=1);

namespace Sitegeist\Noderobis\Utility;

class ConfigurationUtility
{
    /**
     * @param array<int|string,mixed> $array
     * @param string $searchKey
     * @return array<int, string>
     */
    public static function findConfigurationPathesByKey(array $array, string $searchKey): array
    {
        $result = [];
        foreach ($array as $key => $sub) {
            if (is_array($sub)) {
                if (array_key_exists($searchKey, $sub)) {
                    $result[] = (string)$key;
                    continue;
                }
                $subResults = self::findConfigurationPathesByKey($sub, $searchKey);
                if (count($subResults) > 0) {
                    foreach ($subResults as $subResult) {
                        $result[] = $key . '.' . $subResult;
                    }
                }
            }
        }
        return $result;
    }
}
