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
    private const ATTRIBUTE_LOAD_MODE_FULL = 1;
    private const ATTRIBUTE_LOAD_MODE_FLAT = 2;

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
     * @return PimObject The object
     */
    public function getByMenuId($menuId)
    {
        $menu = $this->getMenuById($menuId);
        if ($menu) return $menu->getObject();
        return null;
    }

    public function getMenuById($menuId)
    {
        /** @var Menu */
        $menuObj = $this->store->getObjectByIdentifier($menuId, Menu::class);
        if ($menuObj != null) {
            return $menuObj;
        }

        $menuObjs = $this->loadMenuBy($this->_q()->expr()->eq('m.Id', $menuId));
        if (!empty($menuObjs)) {
            return current($menuObjs)[0];
        }
        return null;
    }

    /**
     * @param int $menuId
     * @return PimObject[]
     */
    public function getParentPathForMenuId($menuId) {
        $menu = $this->getMenuById($menuId);
        $path = array_filter(explode('/', $menu->getPath()));
        $parents = $this->getByMenuIds($path);
        // Ensure correct sorting
        $parents = GeneralUtilities::toDictionary($parents, function($p) { return $p->getMenuId(); });
        $ret = [];
        foreach ($path as $p) {
            $ret[] = $parents[$p];
        }
        return $ret;
    }

    /**
     *
     * @param int $type Object type, either {@see PimObject::TypeGroup} or {@see PimObject::TypeProduct}
     * @param int $id The object's id
     * @return PimObject|null The object
     */
    public function getObjectById($type, $id) {
        switch ($type) {
            case PimObject::TypeGroup:
                $class = Group::class;
                $table = 'Groups';
                break;
            case PimObject::TypeProduct:
                $class = Product::class;
                $table = 'Product';
                break;
            default:
                throw new \Exception('Invalid object type');
        }

        $existing = $this->store->getObjectByIdentifier($id, $class);
        if ($existing) {
            return $existing;
        }

        $q = $this->_q();
        $q->select('*')
            ->from($table)
            ->where($q->expr()->eq('Id', $id));

        $res = $q->execute();
        $row = $res->fetch();
        if ($row) {
            return $this->createObjectFromRow($row, $type);
        }
        return null;
    }

    /**
     * Loads a single object by menu id
     * @param int[] $menuIds The menu id
     * @return PimObject[] The objects
     */
    public function getByMenuIds($menuIds)
    {
        $toLoad = $this->store->filterKnownIdentifiers($menuIds, Menu::class);

        if (!empty($toLoad)) {
            $this->loadMenuBy($this->_q()->expr()->in('m.Id', $toLoad));
        }

        $menus = $this->store->getObjectsByIdentifiers($menuIds, Menu::class);
        $objects = ObjectHelper::getObjectsFromMenus($menus);

        // Must be cached already here
        return $this->restrictionService->filterRestrictionObjects($objects);
    }

    /**
     * Returns a Prodcut by its name
     * @param string $objectName The product's name
     * @return Product
     */
    public function getProductByName($objectName)
    {
        $q = $this->_q();
        $q->select('m.Id')
            ->from('Product', 'p')
            ->innerJoin('p', 'Menu', 'm', $q->expr()->eq('m.ProductId', 'p.Id'))
            ->where($q->expr()->eq('Name', $q->createNamedParameter($objectName)));
        $res = $q->execute()->fetch();
        if ($res) {
            return $this->getByMenuId($res['Id']);
        }
        return null;
    }

    /**
     * Finds objects by a given attribute value
     * @param string $attributeName The attribute's name
     * @param string $value The value to find
     * @param int $entityType Types of entities to find. Must be {@see PimObject::TypeGroup}, {@see PimObject::TypeProduct} or {@see PimObject::TypeNone}. If None, finds all types
     * @param bool $like If true, performs LIKE query, otherwise equals
     * @return PimObject[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getByAttributeValue($attributeName, $value, $entityType = PimObject::TypeNone, $like = false)
    {
        $qG = '';
        if ($entityType == PimObject::TypeGroup || $entityType == PimObject::TypeNone) {
            $qG = $this->_q();
            $qG->select('gm.Id')
                ->from('GroupValue', 'gv')
                ->innerJoin('gv', 'Menu', 'gm', $qG->expr()->eq('gv.GroupId', 'gm.GroupId'))
                ->innerJoin('gv', 'Feature', 'gf', $qG->expr()->eq('gv.FeatureId', 'gf.Id'))
                ->where($qG->expr()->eq('gf.Name', ':attr'));
            if ($like) {
                $qG->andWhere($qG->expr()->like('gv.ContentPlain', ':val'));
            } else {
                $qG->andWhere($qG->expr()->eq('gv.ContentPlain', ':val'));
            }
            $qG = $qG->getSQL();
        }

        $qP = '';
        if ($entityType == PimObject::TypeProduct || $entityType == PimObject::TypeNone) {
            $qP = $this->_q();
            $qP->select('pm.Id')
                ->from('ProductValue', 'pv')
                ->innerJoin('pv', 'Menu', 'pm', $qP->expr()->eq('pv.ProductId', 'pm.ProductId'))
                ->innerJoin('pv', 'Feature', 'pf', $qP->expr()->eq('pv.FeatureId', 'pf.Id'))
                ->where($qP->expr()->eq('pf.Name', ':attr'));
            if ($like) {
                $qP->andWhere($qP->expr()->like('pv.ContentPlain', ':val'));
            } else {
                $qP->andWhere($qP->expr()->eq('pv.ContentPlain', ':val'));
            }
            $qP = $qP->getSQL();
        }

        $q = implode(' UNION ', array_filter([$qG, $qP]));
        $res = $this->db->getConnection()->executeQuery($q, [':attr' => $attributeName, ':val' => $value]);
        $menuIds = $res->fetchAll();
        $menuIds = GeneralUtilities::flattenArray($menuIds);
        return $this->getByMenuIds($menuIds);
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
        $map = $this->loadAttributesByObjects([$object->getId()], $object->getEntityType());
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
        return $this->getObjectAttributesSubsetInternal($objects, $attributes, self::ATTRIBUTE_LOAD_MODE_FULL);
    }

    /**
     * Returns values for certain attributes for a list of objects.
     * Only the requested attributes are loaded.
     *
     * The attributes are returned as a simple map from attribute name to plain content
     *
     * This function respects overriding inheritance
     *
     * @param PimObject[] $objects The objects for which to get attributes
     * @param string[] $attributes The attribute names. Can be full name (incl. structure name), or pure name
     * @return array Map from Object Identifier (according to {@see ObjectHelper::buildKeyFromObject}) to list of AttributeName => ContentPlain
     */
    public function getObjectAttributesSubsetFlat($objects, $attributes) {
        return $this->getObjectAttributesSubsetInternal($objects, $attributes, self::ATTRIBUTE_LOAD_MODE_FLAT);
    }

    private function getObjectAttributesSubsetInternal($objects, $attributes, $mode) {
        $g = array_filter($objects, function($o) { return $o->isGroup(); });
        $p = array_filter($objects, function($o) { return $o->isProduct(); });

        if ($mode == self::ATTRIBUTE_LOAD_MODE_FULL) {
            $gval = $this->loadObjectAttributesSubset(ObjectHelper::getIdsFromObjects($g), PimObject::TypeGroup, $attributes);
            $pval = $this->loadObjectAttributesSubset(ObjectHelper::getIdsFromObjects($p), PimObject::TypeProduct, $attributes);
        } else if ($mode == self::ATTRIBUTE_LOAD_MODE_FLAT) {
            $gval = $this->loadObjectAttributesSubsetFlat(ObjectHelper::getIdsFromObjects($g), PimObject::TypeGroup, $attributes);
            $pval = $this->loadObjectAttributesSubsetFlat(ObjectHelper::getIdsFromObjects($p), PimObject::TypeProduct, $attributes);
        }

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

        if ($obj->getMenuId() != 0) {
            $obj = clone $obj;
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
        return $this->loadObjectAttributesSubsetInternal($objectIds, $entityType, $attributeNames, self::ATTRIBUTE_LOAD_MODE_FULL);
    }

    protected function loadObjectAttributesSubsetFlat($objectIds, $entityType, $attributeNames)
    {
        return $this->loadObjectAttributesSubsetInternal($objectIds, $entityType, $attributeNames, self::ATTRIBUTE_LOAD_MODE_FLAT);
    }

    private function loadObjectAttributesSubsetInternal($objectIds, $entityType, $attributeNames, $mode)
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

        $buildQuery = function($key, $objectIds, $cond) {
            $q = $this->_q();
            $q->select(DbHelper::getTableColumnAs('Feature', 'f_', 'f'));
            $q->addSelect(DbHelper::getTableColumnAs('FeatureValue', 'fv_', 'fv'));
            $q->addSelect(DbHelper::getTableColumnAs($key.'Value', 'v_', 'v'));
            $q->addSelect('s.OrderNr');
            $q->from('Feature', 'f')
                ->innerJoin('f', 'FeatureValue', 'fv', 'f.Id = fv.Id')
                ->innerJoin('f', $key.'Value', 'v', 'f.Id = v.FeatureId')
                ->leftJoin('f', 'StructureElement', 's', 'f.StructureElementId = s.Id')
                ->where(
                    $q->expr()->andX(
                        $q->expr()->in("v.{$key}Id", $objectIds),
                        $cond
                    )
                )
            ;
            return $q;
        };

        $q1 = $buildQuery($key, $objectIds, $this->_q()->expr()->in('f.Name', ':names'));
        $q2 = $buildQuery($key, $objectIds, $this->_q()->expr()->in('fv.AuxiliaryName', ':names'));

        $sql = $q1->getSQL() . " UNION " . $q2->getSQL();
        $q = $this->_q();
        $q->select('*')
            ->from("($sql)", 'Data')
            ->orderBy('OrderNr', 'DESC'); // Higher OrderNr are on top of hierarchy, 1 = Product (negative are special, like Price)

        $q->setParameter(':names', $attributeNames, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        if ($mode == self::ATTRIBUTE_LOAD_MODE_FULL) {
            return $this->fetchByQuery($q);
        } else if ($mode == self::ATTRIBUTE_LOAD_MODE_FLAT) {
            /*
            // Warm up of SQL
            $s = microtime(true);
            $q->execute();
            $sql = microtime(true) - $s;

            $s = microtime(true);
            $x = $this->fetchByQuery($q);
            $full = microtime(true) - $s;

            $s = microtime(true);
            list($x,$tt) = $this->fetchByQueryFlat($q);
            $arr = microtime(true) - $s;
            */

            return $this->fetchByQueryFlat($q);
        }
    }

    private function fetchByQueryFlat($q)
    {
        /*
        $timers = [
            'id' => 0,
            'attr' => 0,
            'val' => 0,
            'sql' => 0
        ];
        */

//        $t = microtime(true);
        $objectMap = [];
        $res = $q->execute();
//        $timers['sql'] += microtime(true) - $t;
        while ($row = $res->fetch()) {
//            $t = microtime(true);
            if (isset($row['v_ProductId'])) {
                $objectId = $row['v_ProductId'];
            } else if (isset($row['v_GroupId'])) {
                $objectId = $row['v_GroupId'];
            } else {
                continue;
            }
//            $timers['id'] += microtime(true) - $t;

//            $t = microtime(true);
            $name = $row['f_Name'];
            $auxName = $row['fv_AuxiliaryName'];
//            $timers['attr'] += microtime(true) - $t;

//            $t = microtime(true);
            $objectMap[$objectId][GeneralUtilities::sanitizeFluidAccessName($name)] = $row['v_ContentPlain'];
            $objectMap[$objectId][GeneralUtilities::sanitizeFluidAccessName($auxName)] = $row['v_ContentPlain']; // Will be overridden, if also exists in a deeper hierarchy level
//            $timers['val'] += microtime(true) - $t;
        }

//        return [$objectMap, $timers];
        return $objectMap;
    }

    /**
     * Fetches attribute values from a query. Respects inheritance
     * @param \Doctrine\DBAL\Query\QueryBuilder $q The query
     * @return AttributeValue[][] Mapping from objectId to AttributeValue list
     */
    private function fetchByQuery($q)
    {

    	/*
        $timers = [
            'att1' => 0,
            'att2' => 0,
            'map1' => 0,
            'map2' => 0,
            'val' => 0
        ];
        */

        $objectMap = [];
        $res = $q->execute();
        while ($row = $res->fetch()) {
//            $t = microtime(true);
            $attrId = $row['f_Id'];
            $attr = $this->attributeRepo->createAttributeFromRow($attrId, $row, ['f_', 'fv_']);
//            $timers['att1'] += microtime(true) - $t;

//            $t = microtime(true);
            $attrValue = new AttributeValue($row['v_Id']);
            $attrValue->_setProperty('attribute', $attr);
//            $timers['att2'] += microtime(true) - $t;

//            $t = microtime(true);
            $this->mapper->mapObject($attrValue, $row, 'v_');
//            $timers['map1'] += microtime(true) - $t;

//            $t = microtime(true);
            $objectId = $attrValue->getObjectId();
            if (!array_key_exists($objectId, $objectMap)) {
                $objectMap[$objectId] = [];
            }
//            $timers['map2'] += microtime(true) - $t;

//            $t = microtime(true);
            $objectMap[$objectId][$attr->getSaneName()] = $attrValue;
            $objectMap[$objectId][$attr->getSaneAuxiliaryName()] = $attrValue; // Will be overridden, if also exists in a deeper hierarchy level
//            $timers['val'] += microtime(true) - $t;
        }
        return $objectMap;
    }
}
