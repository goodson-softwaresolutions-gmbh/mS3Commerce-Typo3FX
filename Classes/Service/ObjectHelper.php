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

use Ms3\Ms3CommerceFx\Domain\Model\AbstractEntity;
use Ms3\Ms3CommerceFx\Domain\Model\Menu;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;

class ObjectHelper
{
    private function __construct()
    {
    }

    /**
     * Returns the objects of the given menus
     * @param Menu[] $menus
     * @return PimObject[]
     */
    public static function getObjectsFromMenus($menus)
    {
        return array_map(function($m) {
            return $m->getObject();
        }, $menus);
    }

    /**
     * Returns the ids of the given objects
     * @param AbstractEntity[] $objects
     * @return int[]
     */
    public static function getIdsFromObjects($objects)
    {
        return array_map(function($o) {
            return $o->getId();
        }, $objects);
    }

    /**
     * Retrieves the menuIds of the given objects
     * @param PimObject[] $objects
     * @return int[]
     */
    public static function getMenuIdsFromObjects($objects)
    {
        return array_map(function($o) {
            return $o->getMenuId();
        }, $objects);
    }

}
