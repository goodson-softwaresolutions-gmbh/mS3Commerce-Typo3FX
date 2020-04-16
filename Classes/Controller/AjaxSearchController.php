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

use Ms3\Ms3CommerceFx\Search\ObjectSearch;
use Ms3\Ms3CommerceFx\Search\SearchContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

class AjaxSearchController extends AbstractController
{
    /** @var ObjectSearch */
    private $search;

    private $inputVariables;

    protected $defaultViewObjectName = JsonView::class;

    /**
     * @param ObjectSearch $os
     */
    public function injectObjectSearch(ObjectSearch $os) {
        $this->search = $os;
    }

    public function initializeAction()
    {
        parent::initializeRootId();
        parent::initializeQuerySettings();
        $in = file_get_contents('php://input');
        $this->inputVariables = json_decode($in, true);
    }

    /**
     * @param int $rootId
     */
    public function filterAction($rootId = 0) {
        $context = SearchContext::createContext();

        try {
            $filterAttrs = $this->inputVariables['filterAttributes'];
            $selAttrs = $this->inputVariables['selectedFilters'];
            $multiAttrs = $this->inputVariables['multiAttributes'];

            $selValues = array_filter($selAttrs);
            $settings = $this->settings['ajaxSearch'];

            if ($settings['resultTemplate']) {
                $limit = $settings['pageSize'];
                $start = 0; // TODO ?
                if (isset($settings['resultStructureElement'])) {
                    $resObjects = $this->search->searchObjectsConsolidatedWithFilter($context, $rootId, $settings['resultStructureElement'], $selValues, $multiAttrs, $start, $limit);
                } else {
                    $resObjects = $this->search->searchObjectsWithFilter($context, $rootId, $selValues, $multiAttrs, $start, $limit);
                }

                $resultContent = $this->renderResultTemplate($context, $settings['resultTemplate'], $resObjects);
            } else {
                // Just prepare empty search
                $this->search->searchFilterValuesWithFilter($context, $rootId, $selAttrs, $multiAttrs);
                $resultContent = '';
            }

            $filterValues = $this->search->getAvailableFilterValues($context, $rootId, $filterAttrs, $multiAttrs, $settings['sortFilterValues']);

            /** @var JsonView $view */
            $view = $this->view;
            $view->assign('filter', $filterValues);
            $view->assign('result', $resultContent);
            $view->assign('page', $resObjects['page']);
            $view->setVariablesToRender(['filter', 'result', 'page']);
        } finally {
            $this->search->cleanupSearch($context);
            SearchContext::destroyContext();
        }
    }

    private function renderResultTemplate(SearchContext $context, $template, $result)
    {
        $mgm = GeneralUtility::makeInstance(ObjectManager::class);
        /* @var StandaloneView $view */
        $view = $mgm->get(StandaloneView::class);
        $view->setTemplatePathAndFilename($template);
        $view->assign('result', $result);
        return $view->render();
    }
}
