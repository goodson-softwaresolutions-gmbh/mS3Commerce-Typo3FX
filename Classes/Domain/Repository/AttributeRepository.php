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

class AttributeRepository extends RepositoryBase
{
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
     * Creates a new Attribute record from a given DB record.
     * If the attribute is already loaded, returns the existing instance
     * @param $attributeId The attribute id to create
     * @param $row The db record
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
        return $attr;
    }
}
