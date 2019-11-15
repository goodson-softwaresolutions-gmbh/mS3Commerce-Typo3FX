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
}
