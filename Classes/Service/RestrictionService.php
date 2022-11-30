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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
    public function filterRestrictionTable($tableName, $connection, $structureElementName)
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

        $markForInclusion = function($q, $values, $type) use ($connection, $tableName)
        {
            $conditions = [
                $q->expr()->isNull($type)
            ];
            foreach ($values as $val) {
                $conditions[] = $q->expr()->like("CONCAT(';',$type,';')", "CONCAT('%;',".$q->createNamedParameter($val).",';%')");
            }
            return new CompositeExpression(CompositeExpression::TYPE_OR, $conditions);
        };

        $q = $connection->createQueryBuilder();
        $q->update($tableName)
            ->set('RestrictionFiltered', 1);

        $conditions = ['RestrictionFiltered = 0'];
        if ($this->querySettings->isMarketRestricted()) {
            $vals = $this->querySettings->getStructureElementRestrictionValues($structureElementName);
            $conditions[] = $markForInclusion($q, $vals,'MarketRestriction');
        }
        if ($this->querySettings->isUserRestricted()) {
            $vals = $this->getUserRestrictionValues();
            $conditions[] = $markForInclusion($q, $vals,'UserRestriction');
        }

        $q->where(new CompositeExpression(CompositeExpression::TYPE_AND, $conditions));
        $q->execute();

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
            $v = $this->applyFilter(
                $vals[$k]??[],
                $this->querySettings->getStructureElementRestrictionAttribute($o->getStructureElement()->getName()),
                $this->querySettings->getStructureElementRestrictionValues($o->getStructureElement()->getName())
            );
            if (!$v) {
                $this->invisibleCache[$k] = 1;
                continue;
            }

            $v = $this->applyFilter(
                $vals[$k]??[],
                $this->querySettings->getUserRestrictionAttribute(),
                $this->getUserRestrictionValues()
            );
            if (!$v) {
                $this->invisibleCache[$k] = 1;
                continue;
            }

            $this->visibleCache[$k] = 1;
        }
    }

    private $userRights;
    public function getUserRestrictionValues() {
        if (!$this->userRights) {
            /** @var Context $context */
            $context = GeneralUtility::makeInstance(Context::class);
            $userIsLoggedIn = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
            if(!$userIsLoggedIn) {
                $this->userRights = $this->querySettings->getUserRestrictionNotLoggedInValues();
            } else {
                $tsfe = $this->getTypoScriptFrontendController();
                $userRights = $tsfe->fe_user->user['ms3c_user_rights'];
                $defaultRights = $this->querySettings->getUserRestrictionDefaultValues();
                $rights = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $userRights);
                // TODO Group Rights
                $rights = array_merge($rights, $defaultRights);
                $this->userRights = $rights;
            }
        }
        return $this->userRights;
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

        $v = $values[GeneralUtilities::sanitizeFluidAccessName($attribute)]??null;
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

        $attrs = array_filter(GeneralUtilities::flattenArray([
            $this->querySettings->getUserRestrictionAttribute(),
            // Note: This will load restriction attributes for all levels, not only required.
            // Check will be level-specific
            $this->querySettings->getMarketRestrictionAttributes()
        ]));
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

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
