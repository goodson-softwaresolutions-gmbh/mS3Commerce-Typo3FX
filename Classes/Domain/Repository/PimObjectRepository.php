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

use Ms3\Ms3CommerceFx\Domain\Model\AttributeValue;
use Ms3\Ms3CommerceFx\Domain\Model\Group;
use Ms3\Ms3CommerceFx\Domain\Model\Menu;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Model\PimObjectCollection;
use Ms3\Ms3CommerceFx\Domain\Model\Product;
use Ms3\Ms3CommerceFx\Service\DbHelper;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;
use Ms3\Ms3CommerceFx\Service\RestrictionService;

/**
 * Class PimObjectRepository
 * @package Ms3\Ms3CommerceFx\Domain\Repository
 */
class PimObjectRepository extends RepositoryBase
{
    /** @var AttributeRepository */
    protected $attributeRepo;

    /**
     * @param AttributeRepository $ar
     */
    public function injectAttributeRepository(AttributeRepository $ar) {
        $this->attributeRepo = $ar;
    }

    /** @var RestrictionService */
    protected $restrictionService;
    /**
     * @param RestrictionService $rs
     */
    public function injectRestrictionService(RestrictionService $rs) {
        $this->restrictionService = $rs;
    }

    /**
     * Loads a single object by menu id
     * @param int $menuId The menu id
     * @return Menu The menu
     */
    public function getByMenuId($menuId)
    {
        /** @var Menu */
        $menuObj = $this->store->getObjectByIdentifier($menuId, Menu::class);
        if ($menuObj != null) {
            return $menuObj->getObject();
        }

        $menuObjs = $this->loadMenuBy($this->_q()->expr()->eq('m.Id', $menuId));
        if (!empty($menuObjs)) {
            return current($menuObjs)[0]->getObject();
        }
        return null;
    }

    /**
     * Loads all children of a object
     * @param PimObject $object The object for which to get children
     */
    public function loadChildren($object)
    {
        if ($object->childrenLoaded()) {
            return;
        }
        $menuId = $object->getMenuId();
        if (!$menuId) {
            return;
        }

        $children = $this->loadMenuBy(
            $this->_q()->expr()->eq('m.ParentId', $menuId),
            'm.Ordinal'
        );

        $children = ObjectHelper::getObjectsFromMenus($children[$menuId]);
        $object->_setProperty('children', $children);
    }

    /**
     * Loads attribute values for a single object
     * @param PimObject $object The object for which to get attribute values
     */
    public function loadAttributeValues($object)
    {
        if ($object->attributesLoaded()) {
            return;
        }
        $map = $this->loadAttributesByObjects($object->getId(), $object->getEntityType());
        $object->_setProperty('attributes', $map[$object->getId()]);
    }

    /**
     * Returns values for certain attributes for a list of objects.
     * Only the requested attributes are loaded.
     *
     * The attributes are not stored in the object! So another call for the same attributes and object
     * will load the data again
     *
     * This function respects overriding inheritance
     *
     * @param PimObject[] $objects The objects for which to get attributes
     * @param string[] $attributes The attribute names. Can be full name (incl. structure name), or pure name
     * @return AttributeValue[][] Map from Object Identifier (according to {@see ObjectHelper::buildKeyFromObject}) to AttributeValue list
     */
    public function getObjectAttributesSubset($objects, $attributes) {
        $g = array_filter($objects, function($o) { return $o->isGroup(); });
        $p = array_filter($objects, function($o) { return $o->isProduct(); });

        $gval = $this->loadObjectAttributesSubset(ObjectHelper::getIdsFromObjects($g), PimObject::TypeGroup, $attributes);
        $pval = $this->loadObjectAttributesSubset(ObjectHelper::getIdsFromObjects($p), PimObject::TypeProduct, $attributes);

        $res = [];
        foreach ($gval as $k => $v) {
            $res[ObjectHelper::buildKeyForObject($k, PimObject::TypeGroup)] = $v;
        }
        foreach ($pval as $k => $v) {
            $res[ObjectHelper::buildKeyForObject($k, PimObject::TypeProduct)] = $v;
        }

        return $res;
    }

    /**
     * Loads all objects from menu with given condition and order.
     * Reuses already loaded objects
     * @param mixed $expr The condition. Either a string, or a Doctrine\DBAL Constraint
     * @param string $order The order clause
     * @return Menu[][] The loaded menus, grouped by parent id
     */
    protected function loadMenuBy($expr, $order = '')
    {
        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('Menu', 'menu_', 'm'));
        $q->addSelect(DbHelper::getTableColumnAs('Groups', 'grp_', 'g'));
        $q->addSelect(DbHelper::getTableColumnAs('Product', 'prd_', 'p'));
        $q->from('Menu', 'm')
            ->leftJoin('m', 'Groups', 'g', 'g.Id = m.GroupId')
            ->leftJoin('m', 'Product', 'p', 'p.Id = m.ProductId')
            ->leftJoin('m', 'StructureElement', 's', 'p.StructureElementId = s.Id OR g.StructureElementId = s.Id')
            ;
        $includePageTypes = $this->querySettings->getIncludeUsageTypeIds();
        if (!empty($includePageTypes)) {
            $q->where(
                $q->expr()->andX(
                    $q->expr()->in('s.Type', $includePageTypes),
                    $expr
                )
            );
        } else {
            $q->where($expr);
        }

        if ($order) {
            $q->orderBy($order);
        }

        $retMap = [];
        $res = $q->execute();
        while ($row = $res->fetch()) {
            $menuId = $row['menu_Id'];
            $pId = $row['menu_ParentId'];
            if (!array_key_exists($pId, $retMap)) {
                $retMap[$pId] = [];
            }

            $existing = $this->store->getObjectByIdentifier($menuId, Menu::class);
            if ($existing) {
                $retMap[$pId][] = $existing;
                continue;
            }

            $menuObj = $this->createMenuFromRow($row);
            if ($menuObj) {
                $retMap[$pId][] = $menuObj;
            }
        }

        // Apply filters
        $retObjects = GeneralUtilities::flattenArray($retMap);
        $retObjects = $this->restrictionService->filterRestrictionObjects(ObjectHelper::getObjectsFromMenus($retObjects));
        $retObjectsKeys = ObjectHelper::getKeyFromObjects($retObjects);

        foreach ($retMap as $pid => $menus) {
            $menus = GeneralUtilities::toDictionary($menus, function($m) { return ObjectHelper::getKeyFromObject($m->getObject());});
            $retMap[$pid] = array_values(GeneralUtilities::subset($menus, $retObjectsKeys));
        }

        if (count($retObjects) > 1) {
            PimObjectCollection::createCollection($retObjects);
        }

        return $retMap;
    }

    private function createMenuFromRow($row)
    {
        $menuId = $row['menu_Id'];

        // Create Menu Object
        $menuObj = new Menu($menuId);
        $this->mapper->mapObject($menuObj, $row, 'menu_');

        $obj = $this->createObjectFromRow($row, $menuObj->getObjectEntityType(), [PimObject::TypeGroup => 'grp_', PimObject::TypeProduct => 'prd_']);

        if (!$obj) {
            return null;
        }

        $obj->_setProperty('menuId', $menuId);
        $menuObj->setObject($obj);
        $this->store->registerObject($menuObj);

        return $menuObj;
    }

    private function createObjectFromRow($row, $type, $prefix = '')
    {
        if (is_array($prefix)) {
            $prefix = $prefix[$type];
        }

        // Create PIM Object
        /** @var PimObject $obj */
        $obj = null;
        switch ($type) {
            case PimObject::TypeGroup:
                $existing = $this->store->getObjectByIdentifier($row[$prefix.'Id'], Group::class);
                if ($existing) {
                    return $existing;
                }
                $obj = new Group($row[$prefix.'Id']);
                $this->mapper->mapObject($obj, $row, $prefix);
                $this->store->registerObject($obj);
                return $obj;
            case PimObject::TypeProduct:
                $existing = $this->store->getObjectByIdentifier($row[$prefix.'Id'], Product::class);
                if ($existing) {
                    return $existing;
                }
                $obj = new Product($row[$prefix.'Id']);
                $this->mapper->mapObject($obj, $row, $prefix);
                $this->store->registerObject($obj);
                return $obj;
            default:
                return null;
        }
    }

    /**
     * Loads attributes for multiple objects.
     * @param int[] $objectIds The ids of the objects
     * @param int $entityType The type of the objects (see PimObject::TypeXXX)
     * @return AttributeValue[][] The values, grouped by object id / attribute sane name
     */
    protected function loadAttributesByObjects($objectIds, $entityType)
    {
        if (empty($objectIds)) {
            return [];
        }
        switch ($entityType) {
            case PimObject::TypeGroup:
                $key = 'Group';
                break;
            case PimObject::TypeProduct:
                $key = 'Product';
                break;
            default:
                return [];
        }

        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('Feature', 'f_', 'f'));
        $q->addSelect(DbHelper::getTableColumnAs('FeatureValue', 'fv_', 'fv'));
        $q->addSelect(DbHelper::getTableColumnAs($key . 'Value', 'v_', 'v'));
        $q->from('Feature', 'f')
            ->innerJoin('f', 'FeatureValue', 'fv', 'f.Id = fv.Id')
            ->innerJoin('f', $key . 'Value', 'v', 'f.Id = v.FeatureId')
            ->leftJoin('f', 'StructureElement', 's', 'f.StructureElementId = s.Id')
            ->where($q->expr()->in("v.{$key}Id", $objectIds))
            ->orderBy('s.OrderNr', 'DESC'); // Higher OrderNr are on top of hierarchy, 1 = Product (negative are special, like Price)

        return $this->fetchByQuery($q);
    }

    protected function loadObjectAttributesSubset($objectIds, $entityType, $attributeNames)
    {
        if (empty($objectIds)) {
            return [];
        }
        switch ($entityType) {
            case PimObject::TypeGroup:
                $key = 'Group';
                break;
            case PimObject::TypeProduct:
                $key = 'Product';
                break;
            default:
                return [];
        }

        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('Feature', 'f_', 'f'));
        $q->addSelect(DbHelper::getTableColumnAs('FeatureValue', 'fv_', 'fv'));
        $q->addSelect(DbHelper::getTableColumnAs($key.'Value', 'v_', 'v'));
        $q->from('Feature', 'f')
            ->innerJoin('f', 'FeatureValue', 'fv', 'f.Id = fv.Id')
            ->innerJoin('f', $key.'Value', 'v', 'f.Id = v.FeatureId')
            ->leftJoin('f', 'StructureElement', 's', 'f.StructureElementId = s.Id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in("v.{$key}Id", $objectIds),
                    $q->expr()->orX(
                        $q->expr()->in("f.Name", ':names'),
                        $q->expr()->in("fv.AuxiliaryName", ':names')
                    )
                )
            )
            ->orderBy('s.OrderNr', 'DESC'); // Higher OrderNr are on top of hierarchy, 1 = Product (negative are special, like Price)

        $q->setParameter(':names', $attributeNames, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        return $this->fetchByQuery($q);
    }

    /**
     * Fetches attribute values from a query. Respects inheritance
     * @param \Doctrine\DBAL\Query\QueryBuilder $q The query
     * @return AttributeValue[][] Mapping from objectId to AttributeValue list
     */
    private function fetchByQuery($q)
    {
        $objectMap = [];
        $res = $q->execute();
        while ($row = $res->fetch()) {
            $attrId = $row['f_Id'];
            $attr = $this->attributeRepo->createAttributeFromRow($attrId, $row, ['f_', 'fv_']);
            $attrValue = new AttributeValue($row['v_Id']);
            $attrValue->_setProperty('attribute', $attr);
            $this->mapper->mapObject($attrValue, $row, 'v_');

            $objectId = $attrValue->getObjectId();
            if (!array_key_exists($objectId, $objectMap)) {
                $objectMap[$objectId] = [];
            }

            $objectMap[$objectId][$attr->getSaneName()] = $attrValue;
            $objectMap[$objectId][$attr->getSaneAuxiliaryName()] = $attrValue; // Will be overridden, if also exists in a deeper hierarchy level
        }
        return $objectMap;
    }
}
