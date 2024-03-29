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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Search\FullTextSearchInterface;
use Ms3\Ms3CommerceFx\Search\SearchContext;
use Ms3\Ms3CommerceFx\Search\SearchQueryUtils;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;
use Ms3\Ms3CommerceFx\Service\RestrictionService;

class SearchRepository extends RepositoryBase
{
    /** @var RestrictionService */
    private $restriction;
    /**
     * @param RestrictionService $rs
     */
    public function injectRestrictionService(RestrictionService $rs) {
        $this->restriction = $rs;
    }

    /** @var FullTextSearchInterface */
    private $ftBackend;
    /**
     * @param FullTextSearchInterface $be
     */
    public function injectTextSearchBackend(FullTextSearchInterface $be) {
        $this->ftBackend = $be;
    }

    /**
     * @var StructureElementRepository
     */
    private $structureElementRepo;
    /**
     * @param StructureElementRepository $ser
     */
    public function injectStructureElementRepository(StructureElementRepository $ser)
    {
        $this->structureElementRepo = $ser;
    }

    /**
     * @var AttributeRepository
     */
    private $attrRepo;

    /**
     * @param AttributeRepository $ar
     */
    public function injectAttributeRepository(AttributeRepository $ar)
    {
        $this->attrRepo = $ar;
    }

    /**
     * Marks all objects in a given path for search
     * @param SearchContext $context
     * @param string $path The Hierarchy path to add for search
     */
    public function findInPath(SearchContext $context, $path) {
        $q = $this->_q();
        $q->select('m.Id, m.Path, m.OrderPath')
            ->from('Menu', 'm')
            ->innerJoin('m', 'Product', 'p', 'p.Id = m.ProductId');

        $q->andWhere($q->expr()->like('m.Path', $path.'%'));

        $cols = array_merge(['MenuId', 'Path', 'Sort'], SearchQueryUtils::addObjectKeyToQuery($q));
        $cols = array_merge($cols, SearchQueryUtils::addRestrictionValuesToQuery($this->querySettings, $q));

        SearchQueryUtils::executeInsert($this->db, $q, $context->getTableName(), $cols);
        $context->isRestrictionFiltered = false;
        $context->consolidatedOnLevel = false;
    }

    /**
     * Marks all objects under a given menu id for search
     * @param SearchContext $context
     * @param int $menuId The menu id to add decedents
     */
    public function findInMenuId(SearchContext $context, $menuId) {
        if (array_key_exists($menuId, $context->handledMenuIds)) {
            return;
        }

        $context->handledMenuIds[$menuId] = true;

        $q = $this->_q();
        $q->select('m.Id, m.Path, m.OrderPath')
            ->from('Menu', 'm')
            ->innerJoin('m', 'Product', 'p', 'p.Id = m.ProductId');

        $q->innerJoin('m', 'Menu', 'pm',
            $q->expr()->like('m.Path', "CONCAT(pm.Path, '/', pm.Id, '%')"));
        $q->andWhere($q->expr()->eq('pm.Id', $menuId));

        $cols = array_merge(['MenuId', 'Path', 'Sort'], SearchQueryUtils::addObjectKeyToQuery($q));
        $cols = array_merge($cols, SearchQueryUtils::addRestrictionValuesToQuery($this->querySettings, $q));

        SearchQueryUtils::executeInsert($this->db, $q, $context->getTableName(), $cols);
        $context->isRestrictionFiltered = false;
        $context->consolidatedOnLevel = false;
    }

    /**
     * Marks all objects matched by full text search for search
     * @param SearchContext $context The search context
     * @param int $shopId The shop to search in
     * @param string $term The search term
     * @param int $menuId Optional restriction to menu sub tree
     */
    public function findByFullText(SearchContext $context, $shopId, $term, $menuId = 0)
    {
        if (array_key_exists($menuId, $context->handledFullTextMenuIds)) {
            return;
        }

        $context->handledFullTextMenuIds[$menuId] = true;

        $this->ftBackend->insertFullTextMatches($context->getTableName(), $shopId, $term, $menuId);

        $context->isRestrictionFiltered = false;
        $context->consolidatedOnLevel = false;
    }

    /**
     * Filters objects marked for search my global restrictions (market/user)
     * @param SearchContext $context
     * @throws \Exception
     */
    public function filterRestrictions(SearchContext $context)
    {
        if ($context->isRestrictionFiltered) {
            return;
        }

        $this->restriction->filterRestrictionTable($context->getTableName(), $this->db->getConnection(), $this->structureElementRepo->getProductLevel()->getName());
        $context->isRestrictionFiltered = true;
        $context->consolidatedOnLevel = false;
    }

    /**
     * Fetches the menu ids for found search objects. Supports windowing
     * @param SearchContext $context
     * @param int $start Window start
     * @param int $limit Window size
     * @return mixed[] The objects, key MenuId contains the menu ids
     * @throws \Exception
     */
    public function fetchMenuIds(SearchContext $context, $start, $limit) {
        $this->filterRestrictions($context);

        $sql = $this->_q()
            ->select('MenuId')
            ->from($context->getTableName())
            ->orderBy('Sort')
            ->getSQL();

        if ($limit > 0) $sql .= ' LIMIT ' . intval($start) .', ' . intval($limit);
        return $this->db->getConnection()->executeQuery($sql)->fetchAll();
    }

    /**
     * Consolidates found search objects on a given level. Supports windowing
     * @param SearchContext $context
     * @param string $structureElement The level to consolidate on
     * @param int $inPathId Restriction of results in a certain menu Id. If 0, no restriction
     * @param int $start Window start
     * @param int $limit Window size
     * @return mixed[] The consolidated objects, key MenuId contains the menu ids
     * @throws \Doctrine\DBAL\DBALException
     */
    public function consolidateMenuIds(SearchContext $context, $structureElement, $inPathId, $start, $limit) {
        $this->consolidateResults($context, $structureElement);
        $this->filterConsolidatedResults($context, $structureElement);

        $q = $this->_q();
        $q->select('MIN(m.Id) AS MenuId')
            ->from($context->getTableName('cons'), 'c')
            ->innerJoin('c', 'Menu', 'm', 'c.MenuId = m.Id')
            ->groupBy('m.GroupId')
            ->addGroupBy('m.GroupId')
        ;

        $this->addInPathRestriction($q, $inPathId, 'm');

        $sql = $q->getSQL();
        $q = $this->_q() // ORDER Column must be in select for DISTINCT
            ->select('m.Id AS MenuId, m.OrderPath')
            ->from('Menu', 'm')
            ->where($q->expr()->in('m.Id', $sql))
            ->orderBy('m.OrderPath')
            ->distinct()
            ;

        $sql = $q->getSQL();

        if ($limit > 0) $sql .= ' LIMIT ' . intval($start) .', ' . intval($limit);
        return $this->db->getConnection()->executeQuery($sql)->fetchAll();
    }

    /**
     * Counts number of found search objects
     * @param SearchContext $context
     * @return int The number of objects
     * @throws \Exception
     */
    public function countMatches(SearchContext $context) {
        $this->filterRestrictions($context);

        $res = $this->_q()
            ->select('COUNT(*) AS ct')
            ->from($context->getTableName())
            ->execute()
            ->fetch();

        return $res['ct'];
    }

    /**
     * Returns the number of consolidated search objects
     * @param SearchContext $context
     * @param string $structureElement The level to consolidate on
     * @param int $inPathId Restriction of results in a certain menu Id. If 0, no restriction
     * @return int The number of consolidated objects
     */
    public function countConsolidatedMatches(SearchContext $context, $structureElement, $inPathId) {
        $this->consolidateResults($context, $structureElement);

        $q = $this->_q();
        $q->select('MIN(c.MenuId)')
            ->from($context->getTableName('cons'), 'c')
            ->innerJoin('c', 'Menu', 'm', 'c.MenuId = m.Id')
            ->groupBy('m.GroupId')
            ->addGroupBy('m.ProductId')
            ->distinct()
            ;

        $this->addInPathRestriction($q, $inPathId, 'm');

        $sql = $q->getSQL();

        $q = $this->_q();
        $res = $q->select('COUNT(*) AS ct')
            ->from("($sql) AS t")
            ->execute()
            ->fetch();

        return $res['ct'];
    }

    /**
     * @param QueryBuilder $q
     * @param int $id
     * @param string $menuAlias
     */
    private function addInPathRestriction($q, $id, $menuAlias) {
        if ($id) {
            $q->innerJoin($menuAlias, 'Menu', 'root', $q->expr()->andX(
                $q->expr()->eq('root.Id', $id),
                $q->expr()->like("$menuAlias.Path", 'CONCAT(root.Path,"/",root.Id,"%")')
            ));
        }
    }

    private function consolidateResults(SearchContext $context, $structureElement) {
        $this->filterRestrictions($context);

        if ($context->consolidatedOnLevel != $structureElement) {
            // Consolidate: Make temp table for climbing up hierarchy
            // Direct approach using Path and LIKE is not fast enough in MySQL...

            $this->createConsolidationTable($context);

            $structure = $this->structureElementRepo->getStructureElementByName($structureElement);
            if ($structure === null) {
                return;
            }

            $q = $this->_q();
            $q->select('m.Id, m.StructureElementId, m.ParentId')
                ->from($context->getTableName(), 't')
                ->innerJoin('t', 'Menu', 'm', 't.MenuId = m.Id');

            if ($context->isAttributeFiltered()) {
                // Only get those matching all filters
                $ct = count($context->filterAttributes);
                $q->where("t.filter_sum = $ct");
            }

            SearchQueryUtils::executeInsert($this->db, $q, $context->getTableName('cons'), ['MenuId', 'StructureElementId', 'ParentId']);

            // Loop up in hierarchy until no more changes
            do {
                $q = $this->_q();
                $q->update($context->getTableName('cons') . ' AS t, Menu AS p')
                    ->set('t.MenuId', 'p.Id')
                    ->set('t.StructureElementId', 'p.StructureElementId')
                    ->set('t.ParentId', 'p.ParentId')
                    ->where('p.Id = t.ParentId')
                    ->andWhere($q->expr()->neq('t.StructureElementId', $structure->getId()));

                $ct = $q->execute();
            } while ($ct > 0);

            $context->consolidatedOnLevel = $structureElement;
        }
    }

    private function filterConsolidatedResults(SearchContext $context, $structureElement)
    {
        $this->injectFilterColumnsForConsolidation($context, $structureElement);
        $this->restriction->filterRestrictionTable($context->getTableName('cons'), $this->db->getConnection(), $structureElement);
    }

    private function injectFilterColumnsForConsolidation(SearchContext $context, $structureElement)
    {
        $q = $this->_q();
        $q->update($context->getTableName('cons') . ' AS s, Menu AS m')
            ->set('s.ObjectKey', "CONCAT('" . PimObject::TypeGroup . ":', m.GroupId)")
            ->where($q->expr()->eq('s.MenuId', 'm.Id'));
        $ct = $q->execute();

        if ($this->querySettings->isMarketRestricted()) {
            $strElem = $this->structureElementRepo->getStructureElementByName($structureElement);
            $attr = $this->attrRepo->getEffectiveAttributeForStructureElement($this->querySettings->getStructureElementRestrictionAttribute($structureElement), $strElem->getOrderNr());
            if ($attr != null) {
                $q = $this->_q();
                $q->update($context->getTableName('cons') . ' AS s, Menu AS m, GroupValue AS gv')
                    ->set('s.MarketRestriction', 'gv.ContentPlain')
                    ->where($q->expr()->andX(
                        $q->expr()->eq('s.MenuId', 'm.Id'),
                        $q->expr()->eq('m.GroupId', 'gv.GroupId'),
                        $q->expr()->eq('gv.FeatureId', $attr->getId())
                    ));
                $ct = $q->execute();
            }
        }

        if ($this->querySettings->isUserRestricted()) {
            $strElem = $this->structureElementRepo->getStructureElementByName($structureElement);
            $attr = $this->attrRepo->getEffectiveAttributeForStructureElement($this->querySettings->getUserRestrictionAttribute(), $strElem->getOrderNr());
            $q = $this->_q();
            $q->update($context->getTableName('cons') . ' AS s, Menu AS m, GroupValue AS gv')
                ->set('s.UserRestriction', 'gv.ContentPlain')
                ->where($q->expr()->andX(
                    $q->expr()->eq('s.MenuId', 'm.Id'),
                    $q->expr()->eq('m.GroupId', 'gv.GroupId'),
                    $q->expr()->eq('gv.FeatureId', $attr->getId())
                ));
            $ct = $q->execute();
        }
    }

    public function filterMatchesByAttributes(SearchContext $context, $selectedFilters, $multiAttrs) {
        if ($context->isAttributeFiltered()) {
            if (empty(array_diff_key($selectedFilters, $context->filterAttributes))) {
                // already filtered by the current attributes, ok
                return;
            }

            // Already filtered by something else??
            throw new \Exception('Result already filtered');
        }

        $multiAttrs = array_flip($multiAttrs);

        $this->filterRestrictions($context);
        $context->filterAttributes = [];

        $col = 0;
        $sum = [];
        $mask = [];
        foreach ($selectedFilters as $attrName => $values) {
            $colName = "t.filter_$col";
            $q = $this->_q();
            $q->update("{$context->getTableName()} t, ProductValue pv, Feature f")
                ->set($colName, "1")
                ->where('t.ProductId = pv.ProductId')
                ->andWhere('pv.FeatureId = f.Id')
                ->andWhere($q->expr()->eq('f.Name', $q->createNamedParameter($attrName)));

            // Is user selected value single, or multiple?
            $isArr = false;
            if (is_array($values)) {
                $values = array_filter($values);
                if (count($values) > 1) {
                    $isArr = true;
                } else {
                    // single value array
                    $values = current($values);
                }
            }

            // Is DB value single or multiple?
            if (!array_key_exists($attrName, $multiAttrs)) {
                // DB value is single value
                if (!$isArr) {
                    // Single DB = Single User value
                    $q->andWhere($q->expr()->eq('pv.ContentPlain', $q->createNamedParameter($values)));
                } else {
                    // Single DB IN (Multiple user values)
                    $q->andWhere($q->expr()->in('pv.ContentPlain', $q->createNamedParameter(array_filter($values), Connection::PARAM_STR_ARRAY)));
                }
            } else {
                // DB value is ";"-separated list of values
                if (!$isArr) {
                    // Single User in ;-separated Multiple DB
                    $q->andWhere($q->expr()->like("CONCAT(';',pv.ContentPlain,';')", "CONCAT('%;',{$q->createNamedParameter($values)},';%')"));
                } else {
                    // Multi-Match ;-separated user values with ;-separated Multiple DB values. Use OR with single matches
                    $conditions = [];
                    foreach ($values as $v) {
                        $conditions[] = $q->expr()->like("CONCAT(';',pv.ContentPlain,';')", "CONCAT('%;',{$q->createNamedParameter($v)},';%')");
                    }
                    $q->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $conditions));
                }
            }

            $q->execute();

            $sum[] = $colName;
            $mask[] = $colName.'*'.(1<<$col);

            $context->filterAttributes[$attrName] = $col;

            $col++;
        }

        // Make a column counting how many filters match (sum)
        // and a column with bitmask which filters match (mask)
        $sum = implode('+', $sum);
        $mask = implode('+', $mask);
        $this->_q()
            ->update($context->getTableName(), 't')
            ->set('filter_sum', $sum)
            ->set('filter_mask', $mask)
            ->execute();
    }

    public function getAvailableFilterValues(SearchContext $context, $filterAttributes, $multiAttrs) {
        $this->filterRestrictions($context);

        $addCols = ($context->isAttributeFiltered()) ? ',t.filter_sum,t.filter_mask' : '';

        $q = $this->_q();
        $q->select('a.Name, v.ContentPlain, v.ContentHtml, v.ContentNumber'.$addCols)
            ->from($context->getTableName(), 't')
            ->innerJoin('t', 'ProductValue', 'v', 't.ProductId = v.ProductId')
            ->innerJoin('v', 'Feature', 'a', 'v.FeatureId = a.Id')
            ->where($q->expr()->in('a.Name', $q->createNamedParameter($filterAttributes, Connection::PARAM_STR_ARRAY)))
            ->distinct()
            ->orderBy('a.Name, v.ContentPlain')
        ;

        $values = $q->execute()->fetchAll();

        /* If GROUPS are in temp table:
        // MySQL cannot UNION statements that use temporary tables... so make separate queries
        $q = $this->_q();
        $q->select('a.Name, v.ContentPlain, v.ContentHtml, v.ContentNumber')
            ->from($context->getTableName(), 't')
            ->innerJoin('t', 'GroupValue', 'v', 't.GroupId = v.GroupId')
            ->innerJoin('v', 'Feature', 'a', 'v.FeatureId = a.Id')
            ->where($q->expr()->in('a.Name', $q->createNamedParameter($filterAttributes, Connection::PARAM_STR_ARRAY)))
            ->distinct();

        $values = array_merge($values, $q->execute()->fetchAll());
        */

        $values = GeneralUtilities::groupBy($values, function($v) { return $v['Name']; });
        $this->extractAvailableFiltersValues($context, $values);

        if (!empty($multiAttrs)) {
            $multiAttrs = array_flip($multiAttrs);
            foreach ($values as $attr => &$vals) {
                if (array_key_exists($attr, $multiAttrs)) {
                    $vals = $this->splitMultiValuedAttribute($vals);
                }
            }
        }
        return $values;
    }

    private function splitMultiValuedAttribute($values) {
        $existingValues = [];
        $newVals = [];
        foreach ($values as $v) {
            $split = explode(';', $v['ContentPlain']);
            if (count($split) > 1) {
                // Extract single values
                $singleValues = array_filter(explode(';', $v['ContentPlain']));
                // Watch out for HTML: get enclosing tag
                $matches = [];
                if (preg_match('#(<[^>]+>)(.*)(</[^>]+>)#', $v['ContentHtml'], $matches)) {
                    $pre = $matches[1];
                    $post = $matches[3];
                    $html = array_filter(explode(';', $matches[2]));
                } else {
                    $pre = $post = '';
                    $html = array_filter(explode(';', $v['ContentHtml']));
                }

                for ($i = 0; $i < count($singleValues); $i++) {
                    if (!array_key_exists($singleValues[$i], $existingValues)) {
                        $newVals[] = [
                            'Name' => $v['Name'],
                            'ContentPlain' => $singleValues[$i],
                            'ContentHtml' => $pre . $html[$i] . $post,
                            'ContentNumber' => null
                        ];
                        $existingValues[$singleValues[$i]] = true;
                    }
                }
            } else {
                // Single value, just copy
                if (!array_key_exists($v['ContentPlain'], $existingValues)) {
                    $newVals[] = $v;
                    $existingValues[$v['ContentPlain']] = true;
                }
            }
        }
        return $newVals;
    }

    private function extractAvailableFiltersValues(SearchContext $context, &$values) {
        if (!$context->isAttributeFiltered())
            return;

        $ct = count($context->filterAttributes);
        foreach ($values as $attr => &$items) {
            $s = $ct;
            $m = (1<<$ct)-1;

            if (array_key_exists($attr, $context->filterAttributes)) {
                // a filtered attribute. value must be present in all OTHER attributes
                $s = $s-1;
                $m &= ~(1<<$context->filterAttributes[$attr]);
                $items = array_filter($items, function($row) use ($s, $m) {
                    if ($row['filter_sum'] < $s) return false; // not matched by all (remaining) filters
                    if ($row['filter_sum'] > $s) return true; // matched by all
                    // Check if only excluded by itself (color = red AND yellow => red and yellow don't match)
                    return ($row['filter_mask'] & $m) == $m;
                });
            } else {
                $items = array_filter($items, function($row) use ($s) {
                    return $row['filter_sum'] >= $s; // Must match ALL filters
                });
            }
        }

    }


    /**
     * Initializes the search context
     * @param SearchContext $context
     * @throws \Doctrine\DBAL\DBALException
     */
    public function initObjectSearch(SearchContext $context) {
        $this->createObjectFilterTable($context, 0);
    }

    public function initObjectSearchForFilter(SearchContext $context, $filters) {
        $this->createObjectFilterTable($context, count($filters));
    }

    private function createObjectFilterTable(SearchContext $context, $filterCount) {
        if ($context->isInitialized) return;
        $context->isInitialized = true;

        $filterCols = '';
        if ($filterCount > 0) {
            $filterCols = array_map(function ($i) {
                return "filter_$i BIT NOT NULL DEFAULT 0";
            }, range(0, $filterCount-1));

            $filterCols[] = 'filter_mask INT NOT NULL DEFAULT 0';
            $filterCols[] = 'filter_sum INT NOT NULL DEFAULT 0';
            $filterCols = implode(',', $filterCols) . ',';
        }
        $this->db->getConnection()->executeQuery(
        /** @lang MySQL */ <<<XXX
            CREATE TEMPORARY TABLE {$context->getTableName()} (
                MenuId INT NOT NULL,
                Path VARCHAR(127) NULL,
                Sort VARCHAR(127) NULL,
                ObjectKey VARCHAR(15) NOT NULL,
                ProductId INT NULL,
                MarketRestriction VARCHAR(255) NULL,
                UserRestriction VARCHAR(255) NULL,
                RestrictionFiltered BIT NOT NULL DEFAULT 0,
                {$filterCols}
                INDEX idx_menuId (MenuId),
                INDEX idx_sort (Sort),
                INDEX idx_path (Path),
                INDEX idx_objectKey (ObjectKey),
                INDEX idx_product (ProductId),
                INDEX idx_visible (RestrictionFiltered)
            ) ENGINE = Memory DEFAULT CHARACTER SET = utf8mb4;
XXX
        );
    }

    /**
     * Creates a table for object consolidation
     * @param SearchContext $context
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createConsolidationTable(SearchContext $context) {
        $this->dropTempTable($context->getTableName('cons'));
        $this->db->getConnection()->executeQuery(
        /** @lang MySQL */ <<<XXX
            CREATE TEMPORARY TABLE {$context->getTableName('cons')} (
                MenuId INT NOT NULL,
                StructureElementId INT NOT NULL,
                ParentId INT NULL,
                -- For filter after consolidation
                ObjectKey VARCHAR(15) NULL,
                MarketRestriction VARCHAR(255) NULL,
                UserRestriction VARCHAR(255) NULL,
                RestrictionFiltered BIT NOT NULL DEFAULT 0,
                INDEX idx_menuId (MenuId),
                INDEX idx_objectKey (ObjectKey),
                INDEX idx_visible (RestrictionFiltered)
            ) ENGINE = Memory;
XXX
        );
    }

    /**
     * Cleans up an object search
     * @param SearchContext $context
     */
    public function cleanupSearch(SearchContext $context) {
        foreach ($context->getUsedTableNames() as $t) {
            $this->dropTempTable($t);
        }
    }

    /**
     * Drops a given temporary table
     * @param string $tableName The table to drop
     * @throws \Doctrine\DBAL\DBALException
     */
    private function dropTempTable($tableName) {
        $this->db->getConnection()->executeQuery(
        /** @lang MySQL */ <<<XXX
                DROP TEMPORARY TABLE IF EXISTS $tableName;
XXX
        );
    }
}
