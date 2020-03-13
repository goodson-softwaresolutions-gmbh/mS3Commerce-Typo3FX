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

use TYPO3\CMS\Core\SingletonInterface;

class NumberFormatter implements SingletonInterface
{
    private static $thousands = ',';
    private static $comma = '.';

    public static function setDefaultFormat($comma, $thousands) {
        self::$comma = $comma;
        self::$thousands = $thousands;
    }

    public static function defaultFormat($number) {
        return self::format($number, self::$comma, self::$thousands);
    }

    public static function format($number, $comma, $thousands) {
        return number_format($number, 2, $comma, $thousands);
    }
}
