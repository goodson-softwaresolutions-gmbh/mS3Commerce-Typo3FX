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

use Doctrine\DBAL\ParameterType;
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

    /**
     * Loads a single object by menu guid
     * @param string $menuGuid The menu guid
     * @return PimObject The object
     */
    public function getByMenuGuid($menuGuid)
    {
        $menu = $this->getMenuByGuid($menuGuid);
        if ($menu) return $menu->getObject();
        return null;
    }

    /**
     * @param int $menuId
     * @return Menu
     */
    public function getMenuById($menuId)
    {
        /** @var Menu $menuObj */
        $menuObj = $this->store->getObjectByIdentifier($menuId, Menu::class);
        if ($menuObj != null) {
            if ($this->restrictionService->filterRestrictionObjects([$menuObj->getObject()])) {
                return $menuObj;
            }
            return null;
        }

        $menuObjs = $this->loadMenuBy($this->_q()->expr()->eq('m.Id', $menuId));
        if (!empty($menuObjs)) {
            return current($menuObjs)[0];
        }
        return null;
    }

    /**
     * Loads multiple menus by their ids
     * @param int[] $menuIds
     * @return Menu[]
     */
    public function getMenusByIds($menuIds)
    {
        $toLoad = $this->store->filterKnownIdentifiers($menuIds, Menu::class);

        if (!empty($toLoad)) {
            $this->loadMenuBy($this->_q()->expr()->in('m.Id', $toLoad));
        }

        $menus = $this->store->getObjectsByIdentifiers($menuIds, Menu::class);
        $objects = ObjectHelper::getObjectsFromMenus($menus);

        // Must be cached already here
        $realObjects = $this->restrictionService->filterRestrictionObjects($objects);
        $realMenuIds = ObjectHelper::getMenuIdsFromObjects($realObjects);
        return GeneralUtilities::subset($menus, $realMenuIds);
    }

    /**
     * @param string $menuGuid
     * @return Menu
     */
    public function getMenuByGuid($menuGuid)
    {
        $q = $this->_q();
        $menuObjs = $this->loadMenuBy($q->expr()->eq('m.ContextID', $q->createNamedParameter($menuGuid)), '', $q->getParameters());
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
        $obj = $this->getObjectsByIds($type, [$id]);
        if ($obj) {
            return current($obj);
        }
        return null;
    }

    /**
     *
     * @param int $type Object type, either {@see PimObject::TypeGroup} or {@see PimObject::TypeProduct}
     * @param int[] $ids The objects' ids
     * @return PimObject[]|null The objects
     */
    public function getObjectsByIds($type, $ids) {
        if (!is_array($ids) || empty($ids)) return [];
        switch ($type) {
            case PimObject::TypeGroup:
                $class = Group::class;
                $table = 'Groups';
                $joinField = 'GroupId';
                break;
            case PimObject::TypeProduct:
                $class = Product::class;
                $table = 'Product';
                $joinField = 'ProductId';
                break;
            default:
                throw new \Exception('Invalid object type');
        }

        $ids = array_unique($ids);

        $toLoad = $this->store->filterKnownIdentifiers($ids, $class);
        if (!empty($toLoad)) {
            $existingIds = array_diff($ids, $toLoad);
        } else {
            $existingIds = $ids;
        }
        $existing = $this->store->getObjectsByIdentifiers($existingIds, $class);

        if (!empty($toLoad)) {
            // Must add a MenuId to loaded object. Take MIN MenuId...
            // For Min MenuId, GROUP BY must also include all other columns. Build this here:
            $cols = DbHelper::getTableColumnNames($table);
            $cols = array_map(function ($c) {
                return "o.$c";
            }, $cols);
            $cols = implode(',', $cols);

            $q = $this->_q();
            $q->select(DbHelper::getTableColumnAs($table, 'o_', 'o'))
                ->addSelect('MIN(m.Id) AS m_Id')
                ->from($table, 'o')
                ->leftJoin('o', 'Menu', 'm', $q->expr()->eq("m.$joinField", 'o.Id'))
                ->where($q->expr()->in('o.Id', $toLoad))
                ->groupBy("m.$joinField,$cols");

            $res = $q->execute();
            while ($row = $res->fetch()) {
                $obj = $this->createObjectFromRow($row, $type, 'o_');
                $obj->_setProperty('menuId', $row['m_Id']);
                $existing[$row['o_Id']] = $obj;
            }
        }

        $filtered = $this->restrictionService->filterRestrictionObjects($existing);
        // Must restore mapping ObjectId => Object
        $ret = GeneralUtilities::toDictionary($filtered, function($o) { return $o->getId();});
        return $ret;
    }

    /**
     * Loads a single object by menu id
     * @param int[] $menuIds The menu id
     * @return PimObject[] The objects
     */
    public function getByMenuIds($menuIds)
    {
        $menus = $this->getMenusByIds($menuIds);
        return ObjectHelper::getObjectsFromMenus($menus);
    }

    /**
     * Ensures that the given object has a Menu Id set.
     * If it doesn't have one, a menu id is loaded for it
     * @param PimObject $object The object
     */
    public function ensureMenuId($object) {
        $this->ensureMenuIds([$object]);
    }

    /**
     * Ensures that the given objects have a Menu Ids set.
     * If an object doesn't have one, a menu id is loaded for it
     * @param PimObject[] $objects
     */
    public function ensureMenuIds($objects)
    {
        $toLoad = array_filter($objects, function($o) { return !$o->getMenuId(); });
        if (empty($toLoad)) return;

        $products = array_filter($toLoad, function($o) { return $o->isProduct(); });
        $groups = array_filter($toLoad, function($o) { return $o->isGroup(); });

        $products = ObjectHelper::getIdsFromObjects($products);
        $groups = ObjectHelper::getIdsFromObjects($groups);

        $q = $this->_q();
        $q->select("IFNULL( CONCAT('".PimObject::TypeProduct.":' , ProductId), CONCAT('".PimObject::TypeGroup.":', GroupId) ), MIN(Id)")
            ->from('Menu')
            ->where(
                $q->expr()->or(
                    $q->expr()->in('ProductId', $q->createNamedParameter($products, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)),
                    $q->expr()->in('GroupId', $q->createNamedParameter($groups, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY))
                )
            )
            ->groupBy('ProductId, GroupId')
            ;

        $res = $q->execute()->fetchAllKeyValue();
        if ($res) {
            foreach ($objects as &$o) {
                $k = ObjectHelper::getKeyFromObject($o);
                if (isset($res[$k])) {
                    $o->_setProperty('menuId', $res[$k]);
                }
            }
        }
    }

    /**
     * Returns a Prodcut by its Object GUID
     * @param string $guid The product's guid
     * @param int $shopId The shop to search in (if 0 uses global query settings)
     * @return Product
     */
    public function getProductByName($guid, $shopId = 0)
    {
        $q = $this->_q();
        $q->select('m.Id')
            ->from('Product', 'p')
            ->innerJoin('p', 'Menu', 'm', $q->expr()->eq('m.ProductId', 'p.Id'))
            ->where($q->expr()->eq('Name', $q->createNamedParameter($guid)));
        $this->shopService->addShopIdRestriction($q, 'm.Id', $shopId ?: $this->querySettings->getShopId());
        $res = $q->execute()->fetchOne();
        if ($res) {
            return $this->getByMenuId($res);
        }
        return null;
    }

    /**
     * Returns a Prodcut by its name
     * @param string $objectName The product's name
     * @param int $shopId The shop to search in (if 0 uses global query settings)
     * @return Product
     */
    public function getProductByGuid($objectName, $shopId = 0)
    {
        $q = $this->_q();
        $q->select('m.Id')
            ->from('Product', 'p')
            ->innerJoin('p', 'Menu', 'm', $q->expr()->eq('m.ProductId', 'p.Id'))
            ->where($q->expr()->eq('AsimOid', $q->createNamedParameter($objectName)));
        $this->shopService->addShopIdRestriction($q, 'm.Id', $shopId ?: $this->querySettings->getShopId());
        $res = $q->execute()->fetchOne();
        if ($res) {
            return $this->getByMenuId($res);
        }
        return null;
    }

    public function getProductsByNames($objectNames, $shopId = 0)
    {
        $q = $this->_q();
        $q->select('MIN(m.Id)')
            ->from('Product', 'p')
            ->innerJoin('p', 'Menu', 'm', $q->expr()->eq('m.ProductId', 'p.Id'))
            ->where($q->expr()->in('Name', $q->createNamedParameter($objectNames, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)))
            ->groupBy('p.Id')
        ;
        $this->shopService->addShopIdRestriction($q, 'm.Id', $shopId ?: $this->querySettings->getShopId());
        $res = $q->execute()->fetchFirstColumn();
        if ($res) {
            return $this->getByMenuIds($res);
        }
        if (is_array($res) && empty($res)) {
            return [];
        }
        return null;
    }

    /**
     * Finds objects by a given attribute value
     * @param string $attributeName The attribute's name
     * @param string|string[] $value The value to find
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
                if (is_array($value)) {
                    $qG->andWhere($qG->expr()->in('gv.ContentPlain', ':val'));
                } else {
                    $qG->andWhere($qG->expr()->eq('gv.ContentPlain', ':val'));
                }
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
                if (is_array($value)) {
                    $qP->andWhere($qP->expr()->in('pv.ContentPlain', ':val'));
                } else {
                    $qP->andWhere($qP->expr()->eq('pv.ContentPlain', ':val'));
                }
            }
            $qP = $qP->getSQL();
        }

        $q = implode(' UNION ', array_filter([$qG, $qP]));
        $params = [':attr' => $attributeName, ':val' => $value];
        if (is_array($value)) {
            $types = [':val' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY];
        } else {
            $types = [];
        }
        $res = $this->db->getConnection()->executeQuery($q, $params, $types);
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
     * @param array $parameters Named parameters to add to query for expression
     * @return Menu[][] The loaded menus, grouped by parent id
     */
    protected function loadMenuBy($expr, $order = '', $parameters = [])
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

        if ($parameters) {
            $q->setParameters($parameters);
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
                $obj = $this->objectCreation->createGroup($row[$prefix.'Id']);
                $this->mapper->mapObject($obj, $row, $prefix);
                $this->store->registerObject($obj, Group::class);
                return $obj;
            case PimObject::TypeProduct:
                $existing = $this->store->getObjectByIdentifier($row[$prefix.'Id'], Product::class);
                if ($existing) {
                    return $existing;
                }
                $obj = $this->objectCreation->createProduct($row[$prefix.'Id']);
                $this->mapper->mapObject($obj, $row, $prefix);
                $this->store->registerObject($obj, Product::class);
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
