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
use Ms3\Ms3CommerceFx\Domain\Model\Product;
use Ms3\Ms3CommerceFx\Service\DbHelper;

/**
 * Class PimObjectRepository
 * @package Ms3\Ms3CommerceFx\Domain\Repository
 */
class PimObjectRepository extends RepositoryBase
{
    public function getByMenuId($menuId)
    {
        /** @var Menu */
        $menuObj = $this->store->getObjectByIdentifier($menuId, Menu::class);
        if ($menuObj != null) {
            return $menuObj->getObject();
        }

        $menuObjs = $this->loadMenuBy($this->_q()->expr()->eq('m.Id', $menuId));
        if (!empty($menuObjs)) {
            return $menuObjs[0]->getObject();
        }
        return null;
    }

    public function getChildren($menuId)
    {
        $children = $this->loadMenuBy(
            $this->_q()->expr()->eq('m.ParentId', $menuId),
            'm.Ordinal'
        );

        return array_map(function($m) { return $m->getObject(); }, $children);
    }

    /**
     * @param PimObject $object
     */
    public function loadAttributeValues(PimObject $object)
    {
        switch ($object->getEntityType()) {
            case PimObject::TypeGroup:
                $key = 'Group';
                break;
            case PimObject::TypeProduct:
                $key = 'Product';
                break;
            default:
                return;
        }

        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('Feature', 'f_', 'f'));
        $q->addSelect(DbHelper::getTableColumnAs('FeatureValue', 'fv_', 'fv'));
        $q->addSelect(DbHelper::getTableColumnAs($key.'Value', 'v_', 'v'));
        $q->from('Feature', 'f')
            ->innerJoin('f', 'FeatureValue', 'fv', 'f.Id = fv.Id')
            ->innerJoin('f', $key.'Value', 'v', 'f.Id = v.FeatureId')
            ->where($q->expr()->eq("v.{$key}Id", $object->getId()));

        $attrs = [];
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
            $attrs[$attr->getSaneName()] = $attrValue;
        }
        $object->_setProperty('attributes', $attrs);
    }

    /**
     * @param $expr
     * @param string $order
     * @return Menu[]
     */
    private function loadMenuBy($expr, $order = '')
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

        $retMenus = [];
        $res = $q->execute();
        while ($row = $res->fetch()) {
            $menuId = $row['menu_Id'];
            $existing = $this->store->getObjectByIdentifier($menuId, Menu::class);
            if ($existing) {
                $retMenus[] = $existing;
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
                    // NOOP
            }

            if ($obj) {
                $obj->_setProperty('menuId', $menuId);
                $menuObj->setObject($obj);
                $this->store->registerObject($menuObj);
                $this->store->registerObject($obj);
                $retMenus[] = $menuObj;
            }
        }

        return $retMenus;
    }
}
