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

use Ms3\Ms3CommerceFx\Domain\Model\AttributeValue;
use Ms3\Ms3CommerceFx\Domain\Model\Menu;
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
        $keys = array_keys($objects);

        // Remove things we already know
        $invisibles = array_intersect($this->invisibleCache, $keys);
        $visibles = array_intersect($this->visibleCache, $keys);
        $checkKeys = array_diff($keys, $invisibles, $visibles);

        // Check restrictions of new objects. updated visible and invisible cache
        $toCheck = GeneralUtilities::subset($objects, $checkKeys);
        $this->checkRestrictions($toCheck);

        // Get only objects that are visible
        $visibleObjects = GeneralUtilities::subset($objects, $this->visibleCache);
        return array_values($visibleObjects);
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
                $this->invisibleCache[] = $k;
                continue;
            }

            // TODO: Check User Restriction

            $this->visibleCache[] = $k;
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

        $v = $v->getContentPlain();
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
        $values = $this->repo->getObjectValueSubset($toLoad, $attrs);
        $values2 = GeneralUtilities::toDictionary($loaded, [ObjectHelper::class, 'getKeyFromObjects'], function($o) { return $o->getAttributes(); });

        return array_merge($values, $values2);
    }

}
