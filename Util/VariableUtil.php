<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Util;

class VariableUtil
{
    /**
     * Merges two arrays recursively, either by allowing four duplicate values
     * for a key or by overwriting identical values.
     *
     * @param $array1
     * @param $array2
     * @param bool|true $overwriteValue
     * @return array
     */
    static function arrayMerge($array1, $array2, $overwriteValue = true)
    {
//        if($overwriteValue) {
//            foreach ($array2 as $key => $Value) {
//                if (array_key_exists($key, $array1) && is_array($Value)) {
//                    $array1[$key] = self::arrayMergeRecursively(
//                        $array1[$key], $array2[$key], $overwriteValue
//                    );
//                } else {
//                    $array1[$key] = $Value;
//                }
//            }
//        } else {
//            $array1 = array_merge_recursive($array1, $array2);
//        }

        $arrayMerged = array_merge_recursive($array1, $array2);
        $arrayUnique = array_unique($arrayMerged, SORT_STRING);

        return $arrayUnique;
    }

    static function arrayFlatten(array $array, $numeric = false) {
        $flattened_array = array();
        array_walk_recursive($array, function($a) use (&$flattened_array) { $flattened_array[] = $a; });

        return $flattened_array;
    }
}