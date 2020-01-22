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

use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Search\SearchContext;
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

        $cols = array_merge(['MenuId', 'Path', 'Sort'], $this->addObjectKeyToQuery($q));
        $cols = array_merge($cols, $this->addRestrictionValuesToQuery($q));

        $this->executeInsert($q, $context->getTableName(), $cols);
        $context->isRestrictionFiltered = false;
        $context->consolidatedOnLevel = false;
    }

    /**
     * Marks all objects under a given menu id for search
     * @param SearchContext $context
     * @param int $menuId The menu id to add decedents
     */
    public function findInMenuId(SearchContext $context, $menuId) {
        $q = $this->_q();
        $q->select('m.Id, m.Path, m.OrderPath')
            ->from('Menu', 'm')
            ->innerJoin('m', 'Product', 'p', 'p.Id = m.ProductId');

        $q->innerJoin('m', 'Menu', 'pm',
            $q->expr()->like('m.Path', "CONCAT(pm.Path, '%')"));
        $q->andWhere($q->expr()->eq('pm.Id', $menuId));

        $cols = array_merge(['MenuId', 'Path', 'Sort'], $this->addObjectKeyToQuery($q));
        $cols = array_merge($cols, $this->addRestrictionValuesToQuery($q));

        $this->executeInsert($q, $context->getTableName(), $cols);
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

        $this->restriction->filterRestrictionTable($context->getTableName(), $this->db->getConnection());
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
     * @param int $start Window start
     * @param int $limit Window size
     * @return mixed[] The consolidated objects, key MenuId contains the menu ids
     * @throws \Doctrine\DBAL\DBALException
     */
    public function consolidateMenuIds(SearchContext $context, $structureElement, $start, $limit) {
        $this->consolidateResults($context, $structureElement);

        $q = $this->_q();
        $sql = $q
            ->select('m.Id AS MenuId, m.OrderPath') // ORDER Column must be in select for DISTINCT
            ->from($context->getTableName('cons'), 't')
            ->innerJoin('t', 'Menu', 'm', 't.MenuId = m.Id')
            ->orderBy('m.OrderPath')
            ->distinct()
            ->getSQL();

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
     * @return int The number of consolidated objects
     */
    public function countConsolidatedMatches(SearchContext $context, $structureElement) {
        $this->consolidateResults($context, $structureElement);

        $q = $this->_q();
        $sql = $q
            ->select('*') // ORDER Column must be in select for DISTINCT
            ->from($context->getTableName('cons'), 't')
            ->distinct()
            ->getSQL();

        $q = $this->_q();
        $res = $q->select('COUNT(*) AS ct')
            ->from("($sql) AS t")
            ->execute()
            ->fetch();

        return $res['ct'];
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

            $this->executeInsert($q, $context->getTableName('cons'));

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

    /*
    public function getAvailableFilterValues(SearchContext $context, $filterAttributes) {
        $this->filterRestrictions($context);

        // Must extrapolate to filter level
        $q = $this->_q();
        $q->select('MIN(OrderNr) AS l')
            ->from('StructureElement', 's')
            ->innerJoin('s','Feature', 'a', 'a.StructureElementId = s.Id')
            ->where('s.OrderNr >= 0')
            ->andWhere($q->expr()->in('a.Name', $q->createNamedParameter($filterAttributes, Connection::PARAM_STR_ARRAY)));
        $minLevelFeature = $q->execute()->fetch()['l'];

        $q = $this->_q();
        $q->select('MIN(OrderNr) AS l')
            ->from($context->getTableName(), 't')
            ->innerJoin('t', 'Menu', 'm', 't.MenuId = m.Id')
            ->innerJoin('m', 'StructureElement', 's', 's.Id = m.StructureElementId')
            ->where('s.OrderNr >= 0');
        $minLevelObjects = $q->execute()->fetch()['l'];

        // MySQL cannot UNION statements that use temporary tables... so make separate queries
        $q = $this->_q();
        $q->select('a.Name, v.ContentPlain, v.ContentHtml, v.ContentNumber')
            ->from($context->getTableName(), 't')
            ->innerJoin('t', 'ProductValue', 'v', 't.ProductId = v.ProductId')
            ->innerJoin('v', 'Feature', 'a', 'v.FeatureId = a.Id')
            ->where($q->expr()->in('a.Name', $q->createNamedParameter($filterAttributes, Connection::PARAM_STR_ARRAY)))
            ->distinct();

        $values = $q->execute()->fetchAll();

        $q = $this->_q();
        $q->select('a.Name, v.ContentPlain, v.ContentHtml, v.ContentNumber')
            ->from($context->getTableName(), 't')
            ->innerJoin('t', 'GroupValue', 'v', 't.GroupId = v.GroupId')
            ->innerJoin('v', 'Feature', 'a', 'v.FeatureId = a.Id')
            ->where($q->expr()->in('a.Name', $q->createNamedParameter($filterAttributes, Connection::PARAM_STR_ARRAY)))
            ->distinct();

        $values = array_merge($values, $q->execute()->fetchAll());

        return GeneralUtilities::toDictionary($values, function($v) { return $v['Name']; });
    }
    */

    /**
     * Adds an object key to the given query to insert into search table
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @return string[] The added insert column names
     */
    private function addObjectKeyToQuery($q)
    {
        $q->addSelect('p.Id');
        $q->addSelect("CONCAT('".PimObject::TypeProduct.":', p.Id) AS ObjectKey");

        return ['ProductId', 'ObjectKey'];
    }

    /**
     * Adds restriction values to the given query to insert into search table
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @return string[] The added insert column names
     */
    private function addRestrictionValuesToQuery($q)
    {
        $cols = [];
        if ($this->querySettings->isMarketRestricted()) {
            $productLevel = $this->structureElementRepo->getProductLevel();
            $marketAttr = $this->attrRepo->getEffectiveAttributeForStructureElement($this->querySettings->getMarketRestrictionAttribute(), $productLevel->getOrderNr());

            if ($marketAttr != null) {
                $q->leftJoin('p', 'ProductValue', 'pv_market', $q->expr()->andX(
                    $q->expr()->eq('pv_market.ProductId', 'p.Id'),
                    $q->expr()->eq('pv_market.FeatureId', $marketAttr->getId())
                ));
                $q->addSelect('pv_market.ContentPlain');
                $cols[] = 'MarketRestriction';
            }
        }

        if ($this->querySettings->isUserRestricted()) {
            $productLevel = $this->structureElementRepo->getProductLevel();
            $marketAttr = $this->attrRepo->getEffectiveAttributeForStructureElement($this->querySettings->getUserRestrictionAttribute(), $productLevel->getId());

            if ($marketAttr != null) {
                $q->leftJoin('p', 'ProductValue', 'pv_user', $q->expr()->andX(
                    $q->expr()->eq('pv_user.ProductId', 'p.Id'),
                    $q->expr()->eq('pv_user.FeatureId', $marketAttr->getId())
                ));
                $q->addSelect('pv_user.ContentPlain');
                $cols[] = 'UserRestriction';
            }
        }

        return $cols;
    }

    /**
     * Executes an INSERT INTO SELECT with the given select query
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @param string $table The table to insert into
     * @param string[] $cols The insert column names. If empty, all destination columns must be selected
     * @throws \Exception
     */
    private function executeInsert($q, $table, $cols = [])
    {
        $colStr = '';
        if (!empty($cols)) {
            $colStr = '('. implode(',', $cols).') ';
        }

        $this->db->getConnection()->executeUpdate(
            "INSERT INTO $table $colStr {$q->getSQL()}",
            $q->getParameters(),
            $q->getParameterTypes()
        );
    }

    /**
     * Initializes the search context
     * @param SearchContext $context
     * @throws \Doctrine\DBAL\DBALException
     */
    public function initObjectSearch(SearchContext $context) {
        if ($context->isInitialized) return;
        $context->isInitialized = true;

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
                ParentId INT NULL
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
