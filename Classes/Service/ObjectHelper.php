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
     * Gets the keys for the given objects
     * @see buildKeyForObject
     * @param PimObject[] $objects The objects
     * @return array The keys
     */
    public static function getKeyFromObjects($objects) {
        return array_map([__CLASS__, 'getKeyFromObject'], $objects);
    }

    /**
     * Gets the key for the given object
     * @see buildKeyForObject
     * @param PimObject $object The object
     * @return string The key
     */
    public static function getKeyFromObject($object) {
        return self::buildKeyForObject($object->getId(), $object->getEntityType());
    }

    /**
     * Builds a unique key internal for an object. This key can be used for storing mixed typed objects in an array
     * @param int $objectId The object's id
     * @param int $entityType The object's entity type
     * @return string A unique key for the object
     */
    public static function buildKeyForObject($objectId, $entityType) {
        return "$entityType:$objectId";
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
