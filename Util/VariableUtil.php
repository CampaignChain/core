<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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

    static function arrayConcatenate($array, $glue = '.', $prefix = '')
    {
        $result = array();

        foreach ($array as $key => $value) {
            $new_key = $prefix . (empty($prefix) ? '' : $glue) . $key;

            if (is_array($value)) {
                $result = array_merge($result, self::arrayConcatenate($value, $glue, $new_key));
            } else {
                $result[$new_key] = $value;
            }
        }

        return $result;
    }

    static function unsetInNumericArrayByValue(array $array, $val)
    {
        if(($key = array_search($val, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;
    }

    static function json2Array($json)
    {
        return json_decode(json_encode($json), true);
    }
}