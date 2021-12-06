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
use Ms3\Ms3CommerceFx\Domain\Model\Categorization;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Service\DbHelper;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;

class CategorizationRepository extends RepositoryBase
{
    /** @var \Ms3\Ms3CommerceFx\Domain\Repository\AttributeRepository */
    protected $attributeRepo;

    /**
     * @param \Ms3\Ms3CommerceFx\Domain\Repository\AttributeRepository $ar
     */
    public function injectAttributeRepository(\Ms3\Ms3CommerceFx\Domain\Repository\AttributeRepository $ar) {
        $this->attributeRepo = $ar;
    }

    /**
     * @param int $categorizationId
     * @return Categorization
     */
    public function getCategorizationById($categorizationId) {
        $cat = $this->store->getObjectByIdentifier($categorizationId, Categorization::class);
        if ($cat == null) {
            $q = $this->_q();
            $q->select('*')
                ->from('featureCompilation', 'c')
                ->where($q->expr()->eq('Id', $categorizationId))
            ;

            $cat = $this->loadFromResult($q->execute());
        }
        return $cat;
    }

    /**
     * @param string $name
     * @param int $shopId The shop to search in (if 0 uses global query settings)
     * @return Categorization
     */
    public function getCategorizationByName($name, $shopId = 0) {
        $q = $this->_q();
        $q->select('*')->from('featureCompilation')->where($q->expr()->eq('Name', $q->createNamedParameter($name)));
        $this->shopService->addShopIdRestriction($q, 'Id', $shopId ?: $this->querySettings->getShopId());
        $id = $q->execute()->fetchOne();
        if ($id) {
            return $this->getCategorizationById($id);
        }
        return null;
    }

    /**
     * Gets all categorizations for a given object
     * @param int $objectId The object's Id
     * @param int $entityType The object's entity type
     * @return Categorization[] The object's categorizations
     */
    public function getCategorizationsForObject($objectId, $entityType) {
        switch ($entityType) {
            case PimObject::TypeGroup:
                $field = 'GroupId';
                break;
            case PimObject::TypeProduct:
                $field = 'ProductId';
                break;
            default:
                return null;
        }

        $q = $this->_q();
        $q->select('f.*')
            ->from('featureCompilation', 'f')
            ->innerJoin('f', 'FeatureCompValue', 'fv', 'f.Id = fv.FeatureCompId')
            ->where($q->expr()->eq("fv.$field", $objectId))
        ;

        return $this->loadFromResult($q->execute());
    }

    /**
     * Gets all categorizations for a list of objects
     * @param int[] $objectIds The object's Id
     * @param int $entityType The objects' entity type
     * @return Categorization[][] Map of object id to categorization list
     */
    public function getCategorizationsForObjectList($objectIds, $entityType) {
        if (empty($objectIds)) {
            return null;
        }
        switch ($entityType) {
            case PimObject::TypeGroup:
                $field = 'GroupId';
                break;
            case PimObject::TypeProduct:
                $field = 'ProductId';
                break;
            default:
                return null;
        }

        $q = $this->_q();
        $q->select('f.*')
            ->addSelect("fv.$field AS _fieldId")
            ->from('featureCompilation', 'f')
            ->innerJoin('f', 'FeatureCompValue', 'fv', 'f.Id = fv.FeatureCompId')
            ->where($q->expr()->in("fv.$field", $objectIds))
        ;

        return $this->loadMappedFromResult($q->execute(), '_fieldId');
    }

    /***
     * Loads the categorizations of an object and assigns them to the object
     * @param PimObject $object
     */
    public function loadCategorizationsForObject($object) {
        if ($object->categorizationsLoaded()) {
            return;
        }
        $cats = $this->getCategorizationsForObject($object->getId(), $object->getEntityType());
        $object->setCategorizations($cats);
    }

    /**
     * @param PimObject[] $objects
     */
    public function loadCategorizationsForObjects($objects) {
        $objects = array_filter($objects, function($o) { return !$o->categorizationsLoaded(); });
        if (empty($objects)) {
            return;
        }

        $groups = array_filter($objects, function($o) { return $o->isGroup(); });
        $products = array_filter($objects, function($o) { return $o->isProduct(); });

        $groupMap = $this->getCategorizationsForObjectList(ObjectHelper::getIdsFromObjects($groups), PimObject::TypeGroup);
        $productMap = $this->getCategorizationsForObjectList(ObjectHelper::getIdsFromObjects($products), PimObject::TypeProduct);

        foreach ($groups as $g) {
            $g->setCategorizations($groupMap[$g->getId()]);
        }

        foreach ($products as $p) {
            $p->setCategorizations($productMap[$p->getId()]);
        }
    }

    /**
     * Fills a categorization's attributes
     * @param Categorization $cat
     */
    public function loadAttributesForCategorization(Categorization $cat) {
        if ($cat->hasAttributesLoaded()) {
            return;
        }

        $categoryMap = $this->loadAttributesByExpression($this->_q()->expr()->eq('c.Id', $cat->getId()));
        $cat->_setProperty('attributes', $categoryMap[$cat->getId()]);
    }

    /**
     * @param \Doctrine\DBAL\Statement $result
     * @param string $prefix
     * @return Categorization[]
     */
    private function loadFromResult($result, $prefix = '') {
        $categories = [];
        while ($row = $result->fetch()) {
            $catId = $row['Id'];
            $cat = $this->store->getObjectByIdentifier($catId, Categorization::class);
            if ($cat == null) {
                $cat = new Categorization($catId);
                $this->mapper->mapObject($cat, $row, $prefix);
                $this->store->registerObject($cat);
            }
            $categories[$catId] = $cat;
        }
        return $categories;
    }

    /**
     * @param \Doctrine\DBAL\Statement $result
     * @param string $mappingColumn
     * @param string $catPrefix
     * @return Categorization[][]
     */
    private function loadMappedFromResult($result, $mappingColumn, $catPrefix = '') {
        $categories = [];
        while ($row = $result->fetch()) {
            $catId = $row[$catPrefix.'Id'];
            $cat = $this->store->getObjectByIdentifier($catId, Categorization::class);
            if ($cat == null) {
                $cat = new Categorization($catId);
                $this->mapper->mapObject($cat, $row, $catPrefix);
                $this->store->registerObject($cat);
            }
            $objId = $row[$mappingColumn];
            if (!array_key_exists($objId, $categories)) {
                $categories[$objId] = [];
            }
            $categories[$objId][$catId] = $cat;
        }
        return $categories;
    }

    /**
     * @param mixed $expr
     * @return Attribute[][] Mapping from categorization id to list of attributes (ordered)
     */
    private function loadAttributesByExpression($expr) {
        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('featureCompilation', 'c_', 'c'))
            ->addSelect(DbHelper::getTableColumnAs('featureComp_feature', 'ca_', 'ca'))
            ->from('featureCompilation', 'c')
            ->innerJoin('c', 'featureComp_feature', 'ca', 'c.Id = ca.FeatureCompId')
            ->where($expr)
            ->orderBy('c.Id')
            ->addOrderBy('ca.Sort');

        $res = $q->execute();
        $attributeMap = [];
        while ($row = $res->fetch()) {
            $catId = $row['c_Id'];
            $attributeMap[$catId][] = $row['ca_FeatureId'];
        }

        // Load all unique attributes used in categorizations
        $attributeIds = array_unique(GeneralUtilities::flattenArray($attributeMap));
        $attributes = $this->attributeRepo->getAttributesByIds($attributeIds);

        $categoryMap = [];
        foreach ($attributeMap as $catId => $attrList) {
            $categoryMap[$catId] = [];
            foreach ($attrList as $attrId) {
                if ($attributes[$attrId]->getName() == 'SMZ_GHOST_NODE')
                    continue;
                $categoryMap[$catId][] = $attributes[$attrId];
            }
        }

        return $categoryMap;
    }
}
