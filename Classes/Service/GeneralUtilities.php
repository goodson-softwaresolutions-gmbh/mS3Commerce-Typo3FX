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

    public static function sanitizeFluidAccessName($name) {
        return preg_replace('/\W/', '_', $name);
    }

    public static function flattenArray($array) {
        $ret = [];
        array_walk_recursive($array, function($a) use (&$ret) { $ret[] = $a; });
        return $ret;
    }
}
