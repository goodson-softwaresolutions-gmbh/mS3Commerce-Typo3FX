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

namespace Ms3\Ms3CommerceFx\Search;

use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Repository\AttributeRepository;
use Ms3\Ms3CommerceFx\Domain\Repository\StructureElementRepository;
use Ms3\Ms3CommerceFx\Persistence\DbBackend;
use Ms3\Ms3CommerceFx\Persistence\QuerySettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SearchQueryUtils
{
    /**
     * Adds an object key to the given query to insert into search table
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @return string[] The added insert column names
     */
    public static function addObjectKeyToQuery($q)
    {
        $q->addSelect('p.Id');
        $q->addSelect("CONCAT('".PimObject::TypeProduct.":', p.Id) AS ObjectKey");

        return ['ProductId', 'ObjectKey'];
    }

    /**
     * Adds an object key to the given query to insert into search table
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @return string[] The added insert column names
     */
    public static function addGroupObjectKeyToQuery($q)
    {
        $q->addSelect('g.Id');
        $q->addSelect("CONCAT('".PimObject::TypeGroup.":', g.Id) AS ObjectKey");

        return ['GroupId', 'ObjectKey'];
    }

    /**
     * Adds restriction values to the given query to insert into search table
     * @param QuerySettings $querySettings
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @return string[] The added insert column names
     */
    public static function addRestrictionValuesToQuery($querySettings, $q)
    {
        // Assume they are reused instances with dependencies injected
        /** @var StructureElementRepository $structure */
        $structure = GeneralUtility::makeInstance(StructureElementRepository::class);
        /** @var AttributeRepository $attr */
        $attr = GeneralUtility::makeInstance(AttributeRepository::class);

        $cols = [];
        if ($querySettings->isMarketRestricted()) {
            $productLevel = $structure->getProductLevel();
            $marketAttr = $attr->getEffectiveAttributeForStructureElement($querySettings->getMarketRestrictionAttribute(), $productLevel->getOrderNr());

            if ($marketAttr != null) {
                $q->leftJoin('p', 'ProductValue', 'pv_market', $q->expr()->andX(
                    $q->expr()->eq('pv_market.ProductId', 'p.Id'),
                    $q->expr()->eq('pv_market.FeatureId', $marketAttr->getId())
                ));
                $q->addSelect('pv_market.ContentPlain');
                $cols[] = 'MarketRestriction';
            }
        }

        if ($querySettings->isUserRestricted()) {
            $productLevel = $structure->getProductLevel();
            $userAttr = $attr->getEffectiveAttributeForStructureElement($querySettings->getUserRestrictionAttribute(), $productLevel->getId());

            if ($userAttr != null) {
                $q->leftJoin('p', 'ProductValue', 'pv_user', $q->expr()->andX(
                    $q->expr()->eq('pv_user.ProductId', 'p.Id'),
                    $q->expr()->eq('pv_user.FeatureId', $userAttr->getId())
                ));
                $q->addSelect('pv_user.ContentPlain');
                $cols[] = 'UserRestriction';
            }
        }

        return $cols;
    }

    /**
     * Adds restriction values to the given query to insert into search table
     * @param QuerySettings $querySettings
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @param string $structureElementName Name of structure element
     * @param $addEmptyIfNotRestricted If true, an empty column will be added to query if not restricted
     * @return string[] The added insert column names
     */
    public static function addGroupRestrictionValuesToQuery($querySettings, $q, $structureElementName, $addEmptyIfNotRestricted)
    {
        // Assume they are reused instances with dependencies injected
        /** @var StructureElementRepository $structure */
        $structure = GeneralUtility::makeInstance(StructureElementRepository::class);
        /** @var AttributeRepository $attr */
        $attr = GeneralUtility::makeInstance(AttributeRepository::class);

        $cols = [];
        if ($querySettings->isMarketRestricted()) {
            $level = $structure->getStructureElementByName($structureElementName);
            $marketAttr = $attr->getEffectiveAttributeForStructureElement($querySettings->getMarketRestrictionAttribute(), $level->getOrderNr());

            if ($marketAttr != null) {
                $q->leftJoin('g', 'GroupValue', 'gv_market', $q->expr()->andX(
                    $q->expr()->eq('gv_market.GroupId', 'g.Id'),
                    $q->expr()->eq('gv_market.FeatureId', $marketAttr->getId())
                ));
                $q->addSelect('gv_market.ContentPlain AS MarketRestriction');
                $cols[] = 'MarketRestriction';
            }
        } else if ($addEmptyIfNotRestricted) {
            $q->addSelect('NULL AS MarketRestriction');
            $cols[] = 'MarketRestriction';
        }

        if ($querySettings->isUserRestricted()) {
            $level = $structure->getStructureElementByName($structureElementName);
            $userAttr = $attr->getEffectiveAttributeForStructureElement($querySettings->getUserRestrictionAttribute(), $level->getId());

            if ($userAttr != null) {
                $q->leftJoin('g', 'GroupValue', 'gv_user', $q->expr()->andX(
                    $q->expr()->eq('gv_user.GroupId', 'g.Id'),
                    $q->expr()->eq('gv_user.FeatureId', $userAttr->getId())
                ));
                $q->addSelect('gv_user.ContentPlain AS UserRestriction');
                $cols[] = 'UserRestriction';
            }
        } else if ($addEmptyIfNotRestricted) {
            $q->addSelect('NULL AS UserRestriction');
            $cols[] = 'UserRestriction';
        }

        return $cols;
    }

    /**
     * Executes an INSERT INTO SELECT with the given select query
     * @param DbBackend $db
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @param string $table The table to insert into
     * @param string[] $cols The insert column names. If empty, all destination columns must be selected
     * @throws \Exception
     */
    public static function executeInsert($db, $q, $table, $cols = [])
    {
        $colStr = '';
        if (!empty($cols)) {
            $colStr = '('. implode(',', $cols).') ';
        }

        $db->getConnection()->executeUpdate(
            "INSERT INTO $table $colStr {$q->getSQL()}",
            $q->getParameters(),
            $q->getParameterTypes()
        );
    }
}
