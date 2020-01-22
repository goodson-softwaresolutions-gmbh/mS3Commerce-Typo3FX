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

namespace Ms3\Ms3CommerceFx\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Ms3\Ms3CommerceFx\Domain\Model\AttributeValue;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;
use Ms3\Ms3CommerceFx\Persistence\QuerySettings;
use TYPO3\CMS\Core\SingletonInterface;

class RestrictionService implements SingletonInterface
{
    private $visibleCache = [];
    private $invisibleCache = [];

    /** @var QuerySettings */
    private $querySettings;
    public function injectQuerySettings(QuerySettings $qs) {
        $this->querySettings = $qs;
    }

    /** @var RepositoryFacade */
    private $repo;
    public function injectRepository(RepositoryFacade $repo) {
        $this->repo = $repo;
    }

    /**
     * Filters a given list of objects for visibility
     * @param PimObject[] $objects The objects to filter
     * @return PimObject[] The filtered objects
     */
    public function filterRestrictionObjects($objects) {
        if (!$this->querySettings->isMarketRestricted() && !$this->querySettings->isUserRestricted()) {
            return $objects;
        }

        $objects = GeneralUtilities::toDictionary($objects, [ObjectHelper::class, 'getKeyFromObject']);

        // Remove things we already know
        $invisibles = array_intersect_key($this->invisibleCache, $objects);
        $visibles = array_intersect_key($this->visibleCache, $objects);
        $toCheck = array_diff_key($objects, $invisibles, $visibles);

        // Check restrictions of new objects. updated visible and invisible cache
        $this->checkRestrictions($toCheck);

        // Get only objects that are visible
        $visibleObjects = GeneralUtilities::subsetKeys($objects, $this->visibleCache);
        return array_values($visibleObjects);
    }

    /**
     * Marks objects as valid by restriction in a given table. The table must have a certain layout:
     * - ObjectKey (VARCHAR): The object's key (as in @see ObjectHelper::buildKeyForObject())
     * - RestrictionFiltered (BIT): If the restriction was already applied to this object
     * - MarketRestriction (VARCHAR): The value of the market restriction attribute
     * - UserRestriction (VARCHAR): The value of the user restriction attribute
     * As a result, all invalid objects will be DELETEd from the table, and all valid are marked as valid
     * @param string $tableName The table name
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function filterRestrictionTable($tableName, $connection)
    {
        if (!$this->querySettings->isMarketRestricted() && !$this->querySettings->isUserRestricted()) {
            $q = $connection->createQueryBuilder();
            $q->update($tableName)
                ->set('RestrictionFiltered', 1)
                ->execute();
            return;
        }

        // Remove known invisibles
        $q = $connection->createQueryBuilder();
        $q->delete($tableName)
            ->where($q->expr()->in('ObjectKey', $q->createNamedParameter(array_keys($this->invisibleCache), Connection::PARAM_STR_ARRAY)))
            ->execute();

        // Mark visible known visibles
        $q = $connection->createQueryBuilder();
        $q->update($tableName)
            ->set('RestrictionFiltered', 1)
            ->where($q->expr()->in('ObjectKey', $q->createNamedParameter(array_keys($this->visibleCache), Connection::PARAM_STR_ARRAY)))
            ->execute();


        $markForInclusion = function($values) use ($connection, $tableName)
        {
            $q = $connection->createQueryBuilder();
            $q->update($tableName)
                ->set('RestrictionFiltered', 1);
            $conditions = [
                $q->expr()->isNull('MarketRestriction')
            ];
            foreach ($values as $val) {
                $conditions[] = $q->expr()->like("CONCAT(';',MarketRestriction,';')", "CONCAT('%;',".$q->createNamedParameter($val).",';%')");
            }
            $q->where($q->expr()->andX(
                'RestrictionFiltered = 0',
                new CompositeExpression(CompositeExpression::TYPE_OR, $conditions)
            ));
            $q->execute();
        };

        if ($this->querySettings->isMarketRestricted()) {
            $markForInclusion($this->querySettings->getMarketRestrictionValues());
        }
        if ($this->querySettings->isUserRestricted()) {
            // TODO
        }

        $connection->createQueryBuilder()
            ->delete($tableName)
            ->where('RestrictionFiltered = 0')
            ->execute();
    }


    /**
     * @param PimObject[] $objects
     */
    private function checkRestrictions($objects) {
        /** @var AttributeValue[][] $vals */
        $vals = $this->getRestrictionAttributeValues($objects);

        foreach ($objects as $k => $o) {
            $v = $this->applyFilter($vals[$k], $this->querySettings->getMarketRestrictionAttribute(), $this->querySettings->getMarketRestrictionValues());
            if (!$v) {
                $this->invisibleCache[$k] = 1;
                continue;
            }

            // TODO: Check User Restriction

            $this->visibleCache[$k] = 1;
        }
    }

    /**
     * @param AttributeValue[] $values
     * @param string $attribute
     * @param array $compare
     * @return bool
     */
    private function applyFilter($values, $attribute, $compare) : bool{
        if (empty($attribute)) {
            return true;
        }

        $v = $values[GeneralUtilities::sanitizeFluidAccessName($attribute)];
        if ($v == null) {
            return true;
        }

        // FOR FULL MODE: $v = $v->getContentPlain();
        if (empty($v)) {
            return true;
        }

        if (strpos($v, "\u{9c}")) {
            $v = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode("\u{9c}", $v);
        } else {
            $v = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $v);
        }

        return !empty(array_intersect($v, $compare));
    }

    /**
     * @param PimObject[] $objects
     * @return AttributeValue[][]
     */
    private function getRestrictionAttributeValues($objects) {
        if (empty($objects)) {
            return [];
        }

        $attrs = array_filter([$this->querySettings->getUserRestrictionAttribute(), $this->querySettings->getMarketRestrictionAttribute()]);
        if (empty($attrs)) {
            return [];
        }

        $toLoad = array_filter($objects, function($o) { return !$o->attributesLoaded();} );
        $loaded = array_filter($objects, function($o) { return $o->attributesLoaded();} );

        // Load attributes for not-loaded objects
        $values = $this->repo->getObjectValueSubsetFlat($toLoad, $attrs);
        // Map loaded objects to flat values
        $values2 = array_map(function($o) {
            return array_map(function($a) {
              return $a->getContentPlain();
            } ,$o->getAttributes());
        }, $loaded);

        /* FULL MODE
        // Load attributes for not-loaded objects
        $values = $this->repo->getObjectValueSubset($toLoad, $attrs);
        $values2 = GeneralUtilities::toDictionary($loaded, [ObjectHelper::class, 'getKeyFromObjects'], function($o) { return $o->getAttributes(); });
        */

        return array_merge($values, $values2);
    }

}
