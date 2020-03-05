<?php
/***************************************************************
 * Part of mS3 Commerce Fx
 * Copyright (C) 2019 Goodson GmbH <http://www.goodson.at>
 *  All rights reserved
 *
 * Dieses Computerprogramm ist urheberrechtlich sowie durch internationale
 * Abkommen geschützt. Die unerlaubte Reproduktion oder Weitergabe dieses
 * Programms oder von Teilen dieses Programms kann eine zivil- oder
 * strafrechtliche Ahndung nach sich ziehen und wird gemäß der geltenden
 * Rechtsprechung mit größtmöglicher Härte verfolgt.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Ms3\Ms3CommerceFx\Service;

class GeneralUtilities
{
    private function __construct() {}

    /**
     * Replaces characters invalid in a fluid accessor by '_'
     * @param string $name The name to sanitize
     * @return string The sane name
     */
    public static function sanitizeFluidAccessName($name) {
        return preg_replace('/\W/', '_', $name);
    }

    /**
     * Flattens a multidimensional array to 1 dimension.
     * @param $array
     * @return array
     */
    public static function flattenArray($array) {
        $ret = [];
        array_walk_recursive($array, function($a) use (&$ret) { $ret[] = $a; });
        return $ret;
    }

    /**
     * Creates a dictionary from an array.
     *
     * Associates every entry of the given with a key, defined by a given callback
     * @param array $array The input array
     * @param callable $keyCallback A callback returning the key for the dictionary
     * @param callable|null $valueCallback An optional callback returning the value of the dictionary. If null, uses the original value
     * @return array
     */
    public static function toDictionary(array $array, callable $keyCallback, callable $valueCallback = null) {
        if ($valueCallback == null) {
            $v = $array;
        } else {
            $v = array_map($valueCallback, $array);
        }

        $k = array_map($keyCallback, $array);
        return array_combine($k, $v);
    }

    public static function groupBy(array $array, callable $keyCallback, callable $valueCallback = null) {
        if ($valueCallback == null) {
            $valueCallback = function($v) { return $v; };
        }
        $res = [];
        foreach ($array as $v) {
            $res[$keyCallback($v)][] = $valueCallback($v);
        }
        return $res;
    }

    /**
     * Returns a subset of a given array, defined by a selection of keys
     * @param array $array The input array
     * @param array $selectKeys The keys in the input array for the subset
     * @return array The subset, containing the elements specified by the selected keys
     */
    public static function subset(array $array, array $selectKeys) {
        $selectKeys = array_fill_keys($selectKeys, 1);
        return self::subsetKeys($array, $selectKeys);
    }

    /**
     * Returns a subset of a given array, defined by a selection of keys
     * @param array $array
     * @param array $selectKeys
     * @return array
     */
    public static function subsetKeys(array $array, array $selectKeys) {
        $res = [];
        foreach ($array as $k => $v) {
            if (array_key_exists($k, $selectKeys)) {
                $res[$k] = $v;
            }
        }
        return $res;
    }
}
