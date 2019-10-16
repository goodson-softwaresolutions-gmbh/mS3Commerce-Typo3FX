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

use Ms3\Ms3CommerceFx\Domain\Model\Attribute;
use Ms3\Ms3CommerceFx\Domain\Model\AttributeValue;
use Ms3\Ms3CommerceFx\Domain\Model\Group;
use Ms3\Ms3CommerceFx\Domain\Model\Menu;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Model\PimObjectCollection;
use Ms3\Ms3CommerceFx\Domain\Model\Product;
use Ms3\Ms3CommerceFx\Service\DbHelper;

/**
 * Class PimObjectRepository
 * @package Ms3\Ms3CommerceFx\Domain\Repository
 */
class PimObjectRepository extends RepositoryBase
{
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
     * Loads all children of a menu id
     * @param int $menuId The menu id for which to get children
     * @return PimObject[] The children
     */
    public function getChildren($menuId)
    {
        $children = $this->loadMenuBy(
            $this->_q()->expr()->eq('m.ParentId', $menuId),
            'm.Ordinal'
        );

        return array_map(function($m) { return $m->getObject(); }, $children[$menuId]);
    }

    public function getChildrenCollection(PimObjectCollection $coll)
    {
        $objects = $coll->all();
        $objects = array_filter($objects, function($o) { return !$o->hasChildren() && $o->getMenuId(); });

        if (empty($objects)) {
            return;
        }

        $menuIds = array_map(function($o) { return $o->getMenuId(); }, $objects);

        $menuMap = $this->loadMenuBy($this->_q()->expr()->in('m.ParentId', $menuIds));
        foreach ($objects as $o) {
            $childObjects = array_map(function($m) { return $m->getObject(); }, $menuMap[$o->getMenuId()]);
            $o->_setProperty('children', $childObjects);
        }
    }

    /**
     * Loads attribute values for a single object
     * @param PimObject $object The object for which to get attribute values
     */
    public function loadAttributeValues(PimObject $object)
    {
        if ($object->hasAttributes()) {
            return;
        }
        $map = $this->loadAttributesByObjects($object->getId(), $object->getEntityType());
        $object->_setProperty('attributes', $map[$object->getId()]);
    }

    public function loadAttributeValuesCollection(PimObjectCollection $coll)
    {
        $groups = $coll->getOfType(PimObject::TypeGroup);
        $prods = $coll->getOfType(PimObject::TypeProduct);

        $groups = array_filter($groups, function($g) { return !$g->hasAttributes(); } );
        $prods = array_filter($prods, function($p) { return !$p->hasAttributes(); } );

        $groupAttrs = $this->loadAttributesByObjects(array_map(function($g) { return $g->getId(); }, $groups), PimObject::TypeGroup);
        $prodAttrs = $this->loadAttributesByObjects(array_map(function($p) { return $p->getId(); }, $prods), PimObject::TypeProduct);

        foreach ($groups as $g) {
            $g->_setProperty('attributes', $groupAttrs[$g->getId()]);
        }

        foreach ($prods as $p) {
            $p->_setProperty('attributes', $prodAttrs[$p->getId()]);
        }
    }

    /**
     * Loads all objects from menu with given condition and order.
     * Reuses already loaded objects
     * @param $expr The condition. Either a string, or a Doctrine\DBAL\Constraint
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
            ->where($expr);
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

            // Create Menu Object
            $menuObj = new Menu($menuId);
            $this->mapper->mapObject($menuObj, $row, 'menu_');

            // Create PIM Object
            /** @var PimObject $obj */
            $obj = null;
            switch ($menuObj->getObjectEntityType()) {
                case PimObject::TypeGroup:
                    $obj = new Group($row['grp_Id']);
                    $this->mapper->mapObject($obj, $row, 'grp_');
                    break;
                case PimObject::TypeProduct:
                    $obj = new Product($row['prd_Id']);
                    $this->mapper->mapObject($obj, $row, 'prd_');
                    break;
                default:
                    // NOOP, will be excluded later
            }

            if ($obj) {
                $obj->_setProperty('menuId', $menuId);
                $menuObj->setObject($obj);
                $this->store->registerObject($menuObj);
                $this->store->registerObject($obj);
                $retMap[$pId][] = $menuObj;
            }
        }

        // Flatten array
        $retMenus = [];
        array_walk_recursive($retMap, function($a) use (&$retMenus) { $retMenus[] = $a; });

        if (count($retMenus) > 1) {
            PimObjectCollection::createCollection(array_map(function ($m) {
                return $m->getObject();
            }, $retMenus));
        }

        return $retMap;
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
        $q->addSelect(DbHelper::getTableColumnAs($key.'Value', 'v_', 'v'));
        $q->from('Feature', 'f')
            ->innerJoin('f', 'FeatureValue', 'fv', 'f.Id = fv.Id')
            ->innerJoin('f', $key.'Value', 'v', 'f.Id = v.FeatureId')
            ->where($q->expr()->in("v.{$key}Id", $objectIds));

        $objectMap = [];
        $res = $q->execute();
        while ($row = $res->fetch()) {
            $attrId = $row['f_Id'];
            /** @var Attribute $attr */
            $attr = $this->store->getObjectByIdentifier($attrId, Attribute::class);
            if ($attr == null) {
                $attr = new Attribute($attrId);
                $this->mapper->mapObject($attr, $row, 'f_');
                $this->mapper->mapObject($attr, $row, 'fv_');
                $this->store->registerObject($attr);
            }

            $attrValue = new AttributeValue($row['v_Id']);
            $attrValue->_setProperty('attribute', $attr);
            $this->mapper->mapObject($attrValue, $row, 'v_');

            $objectId = $attrValue->getObjectId();
            if (!array_key_exists($objectId, $objectMap)) {
                $objectMap[$objectId] = [];
            }

            $objectMap[$objectId][$attr->getSaneName()] = $attrValue;
        }
        return $objectMap;
    }
}
