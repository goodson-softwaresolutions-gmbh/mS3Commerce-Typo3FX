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
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Model\StructureElement;
use Ms3\Ms3CommerceFx\Persistence\DbBackend;
use Ms3\Ms3CommerceFx\Persistence\QuerySettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class RepositoryFacade implements \TYPO3\CMS\Core\SingletonInterface
{
    private static $_instance = null;
    public function __construct()
    {
        if (!self::$_instance) {
            self::$_instance = $this;
        }
    }

    public static function getInstance() {
        if (!self::$_instance) {
            $mgr = GeneralUtility::makeInstance(ObjectManager::class);
            $mgr->get(self::class);
        }
        return self::$_instance;
    }

    private $db;
    /**
     * @param \Ms3\Ms3CommerceFx\Persistence\DbBackend $backend
     */
    public function injectDbBackend(\Ms3\Ms3CommerceFx\Persistence\DbBackend $backend)
    {
        $this->db = $backend;
    }

    private $basket = null;

    /**
     * @return \Extcode\Cart\Domain\Model\Cart\Cart|null
     */
    public function getTxCartsBasket() {
        if ($this->basket == null) {
            $pid = $this->querySettings->getTxCartBasketPid();
            if ($pid !== null) {
                $objManager = GeneralUtility::makeInstance(ObjectManager::class);
                /** @var \Extcode\Cart\Service\SessionHandler $handler */
                $handler = $objManager->get(\Extcode\Cart\Service\SessionHandler::class);
                $this->basket = $handler->restore($pid);
            }
        }
        return $this->basket;
    }

    /**
     * @return DbBackend
     */
    public function getDbConnection() {
        return $this->db;
    }

    /** @var ShopInfoRepository */
    private $shopInfo;
    public function injectShopInfo(ShopInfoRepository $si) {
        $this->shopInfo = $si;
    }
    public function getShopInfoRepository() {
        return $this->shopInfo;
    }

    /** @var PimObjectRepository */
    private $object;
    public function injectObject(PimObjectRepository $or) {
        $this->object = $or;
    }
    public function getObjectRepository() {
        return $this->object;
    }

    /** @var PimObjectCollectionRepository */
    private $objectCollection;
    public function injectObjectCollection(PimObjectCollectionRepository $ocr) {
        $this->objectCollection = $ocr;
    }
    public function getObjectCollectionRepository() {
        return $this->objectCollection;
    }

    /** @var AttributeRepository */
    private $attribute;
    public function injectAttribute(AttributeRepository $ar) {
        $this->attribute = $ar;
    }
    public function getAttributeRepository() {
        return $this->attribute;
    }

    /** @var StructureElementRepository */
    private $structureElement;
    public function injectStructureElement(StructureElementRepository $ser) {
        $this->structureElement = $ser;
    }
    public function getStructureElementRepository() {
        return $this->structureElement;
    }

    /** @var CategorizationRepository */
    private $categorization;
    public function injectCategorization(CategorizationRepository $cr) {
        $this->categorization = $cr;
    }
    public function getCategorizationRepository() {
        return $this->categorization;
    }

    /** @var SearchRepository */
    private $search;
    public function injectSearch(SearchRepository $sr) {
        $this->search = $sr;
    }
    /**
     * @return SearchRepository
     */
    public function getSearchRepository() {
        return $this->search;
    }

    /** @var PriceRepository */
    private $price;
    /**
     * @param PriceRepository $price
     */
    public function injectPrices(PriceRepository $price) {
        $this->price = $price;
    }

    /** @var AvailabilityRepository */
    private $avail;
    /**
     * @param AvailabilityRepository $avail
     */
    public function injectAvailability(AvailabilityRepository $avail) {
        $this->avail = $avail;
    }

    /** @var RelationRepository */
    private $relation;
    /**
     * @param RelationRepository $rel
     */
    public function injectRelations(RelationRepository $rel) {
        $this->relation = $rel;
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
     * @return PimObject
     */
    public function getObjectByMenuId($menuId) {
        return $this->object->getByMenuId($menuId);
    }

    /**
     * @param string $menuGuid
     * @return PimObject
     */
    public function getObjectByMenuGuid($menuGuid) {
        return $this->object->getByMenuGuid($menuGuid);
    }

    /**
     * @param int[] $menuIds
     * @return PimObject[]
     */
    public function getObjectsByMenuIds($menuIds) {
        return $this->object->getByMenuIds($menuIds);
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
     * Returns values for certain attributes for a list of objects.
     * Only the requested attributes are loaded. A simple value array for the plaint content is returned
     * @param PimObject[] $objects The objects
     * @param string[] $attributes The attribute names
     * @return array Map of object key to AttributeName => ContentPlain
     */
    public function getObjectValueSubsetFlat($objects, $attributes) {
        return $this->object->getObjectAttributesSubsetFlat($objects, $attributes);
    }

    /**
     * @param PimObject $object
     */
    public function loadObjectParentPath($object) {
        if ($object->parentPathLoaded()) return;
        $path = $this->object->getParentPathForMenuId($object->getMenuId());
        $object->_setProperty('parentPath', $path);
    }

    /**
     * @param PimObject $object
     * @return PimObject
     */
    public function getParentObject($object) {
        // If parent path is loaded, just take it from there
        if ($object->parentPathLoaded()) {
            $pp = $object->getParentPath();
            return end($pp);
        }
        if ($object->getCollection()) {
            $this->objectCollection->loadParentObjects($object->getCollection());
        } else {
            $this->object->ensureMenuId($object);
        }
        $m = $this->object->getMenuById($object->getMenuId());
        if ($m) {
            return $this->getObjectRepository()->getByMenuId($m->getParentId());
        }
        return null;
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
     * @param PimObject $object
     */
    public function loadObjectPrices($object) {
        if ($object->getCollection()) {
            $this->price->loadPrices($object->getCollection()->getOfType(PimObject::TypeProduct));
        } else {
            $this->price->loadPrices([$object]);
        }
    }

    /**
     * @param PimObject $object
     */
    public function loadObjectAvailability($object) {
        if ($object->getCollection()) {
            $this->avail->loadAvailability($object->getCollection()->getOfType(PimObject::TypeProduct));
        } else {
            $this->avail->loadAvailability([$object]);
        }
    }

    /**
     * @param int $id
     * @return StructureElement
     */
    public function getStructureElementById($id) {
        return $this->structureElement->getStructureElementById($id);
    }

    /**
     * @param PimObject $object
     */
    public function loadObjectRelations($object) {
        if ($object->getCollection()) {
            $this->relation->loadRelations($object->getCollection()->all());
        } else {
            $this->relation->loadRelations([$object]);
        }
    }

    /**
     * @param PimObject $object
     * @param string $relationType
     */
    public function loadObjectRelationChildren($object, $relationType) {
        if ($object->getCollection()) {
            $this->relation->loadRelationChildren($object->getCollection()->all(), $relationType);
        } else {
            $this->relation->loadRelationChildren([$object], $relationType);
        }
    }
}
