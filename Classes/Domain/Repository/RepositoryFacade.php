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

namespace Ms3\Ms3CommerceFx\Domain\Repository;

use Ms3\Ms3CommerceFx\Domain\Model\Categorization;
use Ms3\Ms3CommerceFx\Domain\Model\Menu;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Model\StructureElement;
use Ms3\Ms3CommerceFx\Persistence\QuerySettings;

class RepositoryFacade implements \TYPO3\CMS\Core\SingletonInterface
{
    /** @var PimObjectRepository */
    private $object;
    public function injectObject(PimObjectRepository $or) {
        $this->object = $or;
    }

    /** @var PimObjectCollectionRepository */
    private $objectCollection;
    public function injectObjectCollection(PimObjectCollectionRepository $ocr) {
        $this->objectCollection = $ocr;
    }

    /** @var StructureElementRepository */
    private $structureElement;
    public function injectStructureElement(StructureElementRepository $ser) {
        $this->structureElement = $ser;
    }

    /** @var CategorizationRepository */
    private $categorization;
    public function injectCategorization(CategorizationRepository $cr) {
        $this->categorization = $cr;
    }

    /** @var QuerySettings */
    private $querySettings;
    public function injectQuerySettings(QuerySettings $settings) {
        $this->querySettings = $settings;
    }

    /**
     * @return QuerySettings
     */
    public function getQuerySettings() {
        return $this->querySettings;
    }

    /**
     * @param int $menuId
     * @return Menu
     */
    public function getObjectByMenuId($menuId) {
        return $this->object->getByMenuId($menuId);
    }

    /**
     * Fills in the referenced attributes of a categorization
     * @param Categorization $cat
     */
    public function loadCategorizationAttributes(Categorization $cat) {
        $this->categorization->loadAttributesForCategorization($cat);
    }

    /**
     * Fills in the attribute values of an object
     * @param PimObject $object
     */
    public function loadObjectValues($object) {
        if ($object->getCollection()) {
            $this->objectCollection->loadAttributeValues($object->getCollection());
        } else {
            $this->object->loadAttributeValues($object);
        }
    }

    /**
     * Returns values for certain attributes for a list of objects.
     * Only the requested attributes are loaded. The values are not stored in the object
     * @param PimObject[] $objects The objects
     * @param string[] $attributes The attribute names
     * @return \Ms3\Ms3CommerceFx\Domain\Model\AttributeValue[][]
     */
    public function getObjectValueSubset($objects, $attributes) {
        return $this->object->getObjectAttributesSubset($objects, $attributes);
    }

    /**
     * Fills in the child objects of an object
     * @param PimObject $object
     */
    public function loadObjectChildren($object) {
        if ($object->getCollection()) {
            $this->objectCollection->loadChildren($object->getCollection());
        } else {
            $this->object->loadChildren($object);
        }
    }

    /**
     * Fills in the categorizations of an object
     * @param PimObject $object
     */
    public function loadObjectCategorizations($object) {
        if ($object->getCollection()) {
            $this->categorization->loadCategorizationsForObjects($object->getCollection()->all());
        } else {
            $this->categorization->loadCategorizationsForObject($object);
        }
    }

    /**
     * @param int $id
     * @return StructureElement
     */
    public function getStructureElementById($id) {
        return $this->structureElement->getStructureElementById($id);
    }
}
