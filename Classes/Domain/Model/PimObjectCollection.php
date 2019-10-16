<?php

namespace Ms3\Ms3CommerceFx\Domain\Model;

class PimObjectCollection
{
    /** @var PimObject[] */
    private $objects = [];

    public static function createCollection($objects) {
        $coll = new PimObjectCollection();
        $coll->addObjects($objects);
        return $coll;
    }

    public function addObject(PimObject $object) {
        $this->objects[self::getKey($object)] = $object;
        $object->_setProperty('collection', $this);
    }

    public function addObjects($objects) {
        foreach ($objects as $object) {
            $this->addObject($object);
        }
    }

    public function all() {
        return array_values($this->objects);
    }

    public function getOfType($type) {
        return array_filter($this->objects, function ($o) use ($type) {
            return $o->getEntityType() == $type;
        });
    }

    private static function getKey(PimObject $object) {
        return ($object->getEntityType() . '-' . $object->getId());
    }
}
