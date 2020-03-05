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

namespace Ms3\Ms3CommerceFx\Persistence;

use Ms3\Ms3CommerceFx\Domain\Model\AbstractEntity;
use \TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class StorageSession
 * Reimplementation of \TYPO3\CMS\Extbase\Persistence\Generic\Session
 * Difference is that we don't support reconstituted objects, and is based on AbstractEntity
 * @package Ms3\Ms3CommerceFx\Persistence
 */
class StorageSession implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var ObjectStorage
     */
    private $objectMap;
    private $identifierMap = [];
    private $secondaryMap = [];
    public function __construct()
    {
        $this->objectMap = new ObjectStorage();
    }

    public function hasObject(AbstractEntity $object) : bool
    {
        return $this->objectMap->contains($object);
    }

    public function hasIdentifier($identifier, $className) : bool
    {
        return isset($this->identifierMap[$className][$identifier]);
    }

    /**
     * Removes all stored identifiers from an array of identifiers.
     * @param int[] $identifiers A list of identifiers to filter
     * @param string $className The class type of the identifiers
     * @return int[] All identifiers that are not stored
     */
    public function filterKnownIdentifiers($identifiers, $className) {
        if (!is_array($this->identifierMap[$className])) {
            return $identifiers;
        }
        return array_diff($identifiers, array_keys($this->identifierMap[$className]));
    }

    public function getObjectByIdentifier($identifier, $className)
    {
        return $this->identifierMap[$className][$identifier];
    }

    public function getObjectsByIdentifiers($identifiers, $className) {
        $res = [];
        foreach ($identifiers as $id) {
            $res[$id] = $this->getObjectByIdentifier($id, $className);
        }
        return $res;
    }

    public function getObjectBySecondaryIdentifier($identifier, $className)
    {
        return $this->secondaryMap[$className][$identifier];
    }

    public function getObjectsBySecondaryIdentifiers($identifiers, $className) {
        $res = [];
        foreach ($identifiers as $id) {
            $res[$id] = $this->getObjectBySecondaryIdentifier($id, $className);
        }
        return $res;
    }

    public function getIdentifierByObject($object)
    {
        if ($this->hasObject($object))
            return $this->objectMap[$object];
        return null;
    }

    public function registerObject($object)
    {
        $this->objectMap[$object] = $object->getId();
        $this->identifierMap[get_class($object)][$object->getId()] = $object;
    }

    public function registerObjectSecondary($object, $key)
    {
        $this->secondaryMap[get_class($object)][$key] = $object;
    }

    public function unregisterObject($object)
    {
        unset($this->identifierMap[get_class($object)][$this->objectMap[$object]]);
        $this->objectMap->detach($object);
    }

    public function destroy()
    {
        $this->identifierMap = [];
        $this->objectMap = new ObjectStorage();
    }
}
