<?php

namespace Ms3\Ms3CommerceFx\Domain\Model;

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
        $this->objects[self::getKey($object)] = $object;
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

    private static function getKey(PimObject $object) {
        return ($object->getEntityType() . '-' . $object->getId());
    }
}
