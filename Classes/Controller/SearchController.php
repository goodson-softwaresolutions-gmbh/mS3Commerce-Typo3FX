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

namespace Ms3\Ms3CommerceFx\Controller;


use Ms3\Ms3CommerceFx\Domain\Model\PaginationInfo;
use Ms3\Ms3CommerceFx\Domain\Repository\ShopInfoRepository;
use Ms3\Ms3CommerceFx\Search\ObjectSearch;
use Ms3\Ms3CommerceFx\Search\SearchContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SearchController extends AbstractController
{
    /** @var ObjectSearch */
    private $search;

    /**
     * @param ObjectSearch $os
     */
    public function injectObjectSearch(ObjectSearch $os) {
        $this->search = $os;
    }

    /** @var ShopInfoRepository */
    private $shopInfo;
    /**
     * @param ShopInfoRepository $si
     */
    public function injectShopInfo(ShopInfoRepository $si) {
        $this->shopInfo = $si;
    }

    /**
     * @param int $rootId
     */
    public function searchAction($rootId = 0) {
        if ($rootId == 0) $rootId = $this->rootId;
        $term = GeneralUtility::_GP('term');
        $shop = $this->shopInfo->getByContainedId($rootId);
        $settings = $this->settings['fulltextSearch'];
        $limit = $settings['pageSize'];
        $page = GeneralUtility::_GP('page') ?? 0;
        $start = PaginationInfo::startItemForPage($page, $limit);

        $context = SearchContext::createContext();
        try {
            if ($settings['resultStructureElement']) {
                $res = $this->search->fulltextSearchObjectsConsolidated($context, $shop->getShopId(), $term, $settings['resultStructureElement'], $start, $limit);
            } else {
                $res = $this->search->fulltextSearchObjects($context, $shop->getShopId(), $term, $start, $limit);
            }
            $this->view->assign('result', $res);
            $this->view->assign('term', $term);
        } finally {
            $this->search->cleanupSearch($context);
            SearchContext::destroyContext();
        }
    }
}
