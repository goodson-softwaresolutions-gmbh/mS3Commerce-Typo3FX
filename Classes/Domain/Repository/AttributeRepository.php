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
use Ms3\Ms3CommerceFx\Service\DbHelper;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;

class AttributeRepository extends RepositoryBase
{
    private $structureElement;
    public function injectStructureElement(StructureElementRepository $ser) {
        $this->structureElement = $ser;
    }

    /**
     * Gets an attribute by Id
     * @param int $attributeId The attribute id
     * @return Attribute The attribute
     */
    public function getAttributeById($attributeId) {
        $attr = $this->store->getObjectByIdentifier($attributeId, Attribute::class);
        if ($attr == null) {
            $q = $this->queryByExpression($this->_q()->expr()->eq('f.Id', $attributeId));
            $res = $q->execute()->fetchAll();
            if ($res && !empty($res)) {
                $attr = $this->internalBuildFromRow($attributeId, $res, ['f_', 'fv_']);
            }
        }
        return $attr;
    }

    /**
     * Loads a list of attributes by ids. Already known attributes are not reloaded
     * @param int[] $attributeIds
     * @return Attribute[]
     */
    public function getAttributesByIds($attributeIds) {
        $toLoad = $this->store->filterKnownIdentifiers($attributeIds, Attribute::class);

        if (!empty($toLoad)) {
            // Load unknown attributes
            $q = $this->queryByExpression($this->_q()->expr()->in('f.Id', $toLoad));
            $res = $q->execute();
            while ($row = $res->fetch()) {
                $this->internalBuildFromRow($row['f_Id'], $row, ['f_', 'fv_']);
            }
        }

        return $this->store->getObjectsByIdentifiers($attributeIds, Attribute::class);
    }

    /**
     * Returns an attribute by its sanitized name
     * Note: This function must load all attributes when used the first time, which takes some time. Try to avoid
     * @param string $saneAttributeName The sanitized attribute name
     * @return Attribute|null
     */
    public function getAttributeBySaneName($saneAttributeName) {
        $attr = $this->store->getObjectBySecondaryIdentifier($saneAttributeName, Attribute::class);
        if ($attr) {
            return $attr;
        }

        /// MySQL has REGEXP_REPLACE only in Version 8 (MariaDB already earlier)
        /// So to still be compatible with MySql 5.X, work around SQL search...
        $this->loadAll();
        $attr = $this->store->getObjectBySecondaryIdentifier($saneAttributeName, Attribute::class);
        if ($attr) {
            return $attr;
        }

        /*
         * SQL REGEXP Search variant:
        // attribute name is a sanitized name! Must also sanitize in sql query
        $q = $this->queryByExpression($this->_q()->expr()->eq(GeneralUtilities::sqlSanitizeFliudAccessName('f.Name'), ':name'))->setParameter(':name', $saneAttributeName);
        $res = $q->execute()->fetchAll();
        if ($res && !empty($res)) {
            $attr = $this->internalBuildFromRow($res['f_Id'], $res, ['f_', 'fv_']);
            return $attr;
        }
        */
        return null;
    }

    /**
     * Returns the effective attribute on a given level, considering inheritance
     * @param string $attributeName The attribute name
     * @param string $structureElementOrder The level order
     * @return Attribute|null The effective attribute
     */
    public function getEffectiveAttributeForStructureElement($attributeName, $structureElementOrder)
    {
        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('Feature', 'f_', 'f'))
            ->addSelect(DbHelper::getTableColumnAs('FeatureValue', 'fv_', 'fv'))
            ->from('Feature', 'f')
            ->innerJoin('f', 'FeatureValue', 'fv', 'f.Id = fv.FeatureId')
            ->where('f.Name = :fName')
            ->setParameter(':fName', $this->querySettings->getMarketRestrictionAttribute());

        if ($row = $q->execute()->fetch()) {
            // Direct name
            return $this->internalBuildFromRow($row['f_Id'], $row, ['f_', 'fv_']);
        }

        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('Feature', 'f_', 'f'))
            ->addSelect(DbHelper::getTableColumnAs('FeatureValue', 'fv_', 'fv'))
            ->from('FeatureValue', 'fv')
            ->innerJoin('fv', 'Feature', 'f', 'fv.FeatureId = f.Id')
            ->innerJoin('f', 'StructureElement', 's', 'f.StructureElementId = s.Id')
            ->where($q->expr()->eq('fv.AuxiliaryName', $q->createNamedParameter($attributeName)))
            ->andWhere($q->expr()->gte('s.OrderNr', $structureElementOrder))
            ->orderBy('OrderNr');

        $q->setParameter(':fName', $attributeName);

        $row = $q->execute()->fetch();

        if ($row) {
            return $this->internalBuildFromRow($row['f_Id'], $row, ['f_', 'fv_']);
        }
        return null;
    }

    private function loadAll()
    {
        $q = $this->queryByExpression('1=1');
        $q->andWhere($q->expr()->eq('f.MarketId', $this->querySettings->getMarketId()));
        $q->andWhere($q->expr()->eq('f.LanguageId', $this->querySettings->getLanguageId()));
        $res = $q->execute();
        $attrs = [];
        while ($row = $res->fetch()) {
            $attrs[] = $this->internalBuildFromRow($row['f_Id'], $row, ['f_', 'fv_']);
        }
        return $attrs;
    }

    /**
     * Creates a new Attribute record from a given DB record.
     * If the attribute is already loaded, returns the existing instance
     * @param int $attributeId The attribute id to create
     * @param array $row The db record
     * @param array $prefixes array of prefixes in record for data mapping
     * @return Attribute The existing, or newly created attribute
     */
    public function createAttributeFromRow($attributeId, $row, $prefixes = ['']) {
        /** @var Attribute $attr */
        $attr = $this->store->getObjectByIdentifier($attributeId, Attribute::class);
        if ($attr == null && !empty($row)) {
            $attr = $this->internalBuildFromRow($attributeId, $row, $prefixes);
        }
        return $attr;
    }

    /**
     * @param $expr
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function queryByExpression($expr) {
        $q = $this->_q();
        $q->select(DbHelper::getTableColumnAs('Feature', 'f_', 'f'))
            ->addSelect(DbHelper::getTableColumnAs('FeatureValue', 'fv_', 'fv'))
            ->from('Feature', 'f')
            ->innerJoin('f', 'FeatureValue', 'fv', 'f.Id = fv.Id')
            ->where($expr);
        return $q;
    }

    private function internalBuildFromRow($attributeId, $row, $prefixes) {
        $attr = new Attribute($attributeId);
        foreach ($prefixes as $p) {
            $this->mapper->mapObject($attr, $row, $p);
        }
        $this->store->registerObject($attr);
        $this->store->registerObjectSecondary($attr, $attr->getSaneName());

        return $attr;
    }
}
