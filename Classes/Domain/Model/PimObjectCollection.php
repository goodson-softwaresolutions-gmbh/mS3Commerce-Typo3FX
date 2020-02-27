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

namespace Ms3\Ms3CommerceFx\Domain\Model;

use Ms3\Ms3CommerceFx\Service\ObjectHelper;

class PimObjectCollection
{
    /** @var PimObject[] */
    private $objects = [];

    /**
     * @param PimObject[] $objects
     * @return PimObjectCollection
     */
    public static function createCollection($objects) {
        $coll = new PimObjectCollection();
        $coll->addObjects($objects);
        return $coll;
    }

    /**
     * @param PimObject $object
     */
    public function addObject(PimObject $object) {
        $this->objects[ObjectHelper::getKeyFromObject($object)] = $object;
        $object->_setProperty('collection', $this);
    }

    /**
     * @param PimObject[] $objects
     */
    public function addObjects($objects) {
        foreach ($objects as $object) {
            $this->addObject($object);
        }
    }

    /**
     * @return PimObject[]
     */
    public function all() {
        return array_values($this->objects);
    }

    /**
     * @return int
     */
    public function count() {
        return count($this->objects);
    }

    /**
     * @param int $type
     * @return PimObject[]
     */
    public function getOfType($type) {
        return array_values(
            array_filter($this->objects, function ($o) use ($type) {
                return $o->getEntityType() == $type;
            })
        );
    }
}
