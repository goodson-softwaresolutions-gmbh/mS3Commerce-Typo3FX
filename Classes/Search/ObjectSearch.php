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

use Ms3\Ms3CommerceFx\Domain\Model\PaginationInfo;
use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;
use TYPO3\CMS\Core\SingletonInterface;

class ObjectSearch implements SingletonInterface
{
    /** @var RepositoryFacade */
    private $repo;
    /**
     * @param RepositoryFacade $repo
     */
    public function injectRepository(RepositoryFacade $repo) {
        $this->repo = $repo;
    }

    public function fulltextSearchObjects(SearchContext $context, $shopId, $term, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $s->initObjectSearch($context);
        $s->findByFullText($context, $shopId, $term);

        return $this->fetchAllResults($context, $start, $limit);
    }

    public function fulltextSearchObjectsInMenu(SearchContext $context, $rootId, $term, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $s->initObjectSearch($context);
        $shop = $this->repo->getShopInfoRepository()->getByContainedId($rootId);
        $s->findByFullText($context, $shop->getShopId(), $term, $rootId);

        return $this->fetchAllResults($context, $start, $limit);
    }

    public function fulltextSearchObjectsConsolidated(SearchContext $context, $shopId, $term, $structureElement, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $s->initObjectSearch($context);
        $s->findByFullText($context, $shopId, $term);

        return $this->fetchConsolidatedResults($context, $structureElement, $start, $limit);
    }

    public function fulltextSearchObjectsInMenuConsolidated(SearchContext $context, $rootId, $term, $structureElement, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $s->initObjectSearch($context);
        $shop = $this->repo->getShopInfoRepository()->getByContainedId($rootId);
        $s->findByFullText($context, $shop->getShopId(), $term, $rootId);

        return $this->fetchConsolidatedResults($context, $structureElement, $start, $limit);
    }

    public function searchObjects(SearchContext $context, $rootId, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $s->initObjectSearch($context);
        $s->findInMenuId($context, $rootId);

        return $this->fetchAllResults($context, $start, $limit);
    }

    public function searchObjectsConsolidated(SearchContext $context, $rootId, $structureElement, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $s->initObjectSearch($context);
        $s->findInMenuId($context, $rootId);

        return $this->fetchConsolidatedResults($context, $structureElement, $start, $limit);
    }

    public function searchObjectsWithFilter(SearchContext $context, $rootId, $selectedFilters, $start, $limit) {
        $selectedFilters = array_filter($selectedFilters);
        if (empty($selectedFilters)) {
            return $this->searchObjects($context, $rootId, $start, $limit);
        }

        $s = $this->repo->getSearchRepository();
        $s->initObjectSearchForFilter($context, $selectedFilters);
        $s->findInMenuId($context, $rootId);
        $s->filterMatchesByAttributes($context, $selectedFilters);
        return $this->fetchAllResults($context, $start, $limit);
    }

    public function searchObjectsConsolidatedWithFilter(SearchContext $context, $rootId, $structureElement, $selectedFilters, $start, $limit) {
        $selectedFilters = array_filter($selectedFilters);
        if (empty($selectedFilters)) {
            return $this->searchObjectsConsolidated($context, $rootId, $structureElement, $start, $limit);
        }

        $s = $this->repo->getSearchRepository();
        $s->initObjectSearchForFilter($context, $selectedFilters);
        $s->findInMenuId($context, $rootId);
        $s->filterMatchesByAttributes($context, $selectedFilters);
        return $this->fetchConsolidatedResults($context, $structureElement, $start, $limit);
    }

    public function searchFilterValuesWithFilter(SearchContext $context, $rootId, $selectedFilters) {
        $s = $this->repo->getSearchRepository();
        if (empty($selectedFilters)) {
            $s->initObjectSearch($context);
            $s->findInMenuId($context, $rootId);
        } else {
            $s->initObjectSearchForFilter($context, $selectedFilters);
            $s->findInMenuId($context, $rootId);
            $s->filterMatchesByAttributes($context, $selectedFilters);
        }
    }

    public function getAvailableFilterValues(SearchContext $context, $rootId, $filterAttributes) {
        $s = $this->repo->getSearchRepository();
        $s->initObjectSearch($context);
        $s->findInMenuId($context, $rootId);
        return $s->getAvailableFilterValues($context, $filterAttributes);
    }

    public function cleanupSearch(SearchContext $context) {
        $this->repo->getSearchRepository()->cleanupSearch($context);
    }

    private function fetchAllResults(SearchContext $context, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $menuIds = $s->fetchMenuIds($context, $start, $limit);
        $menuIds = array_map(function($r) { return $r['MenuId']; }, $menuIds);
        $res = $this->repo->getObjectsByMenuIds($menuIds);
        $total = $s->countMatches($context);

        $page = new PaginationInfo($start + 1, count($res), $limit, $total);
        return [
            'objects' => $res,
            'page' => $page
        ];
    }

    private function fetchConsolidatedResults(SearchContext $context, $structureElement, $start, $limit) {
        $s = $this->repo->getSearchRepository();
        $menuIds = $s->consolidateMenuIds($context, $structureElement, $start, $limit);
        $menuIds = array_map(function($r) { return $r['MenuId']; }, $menuIds);
        $res = $this->repo->getObjectsByMenuIds($menuIds);
        $total = $s->countConsolidatedMatches($context, $structureElement);

        $page = new PaginationInfo($start + 1, count($res), $limit, $total);
        return [
            'objects' => $res,
            'page' => $page
        ];
    }
}
