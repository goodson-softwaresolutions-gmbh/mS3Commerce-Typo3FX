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

namespace Ms3\Ms3CommerceFx\ViewHelpers\AjaxSearch;

use Ms3\Ms3CommerceFx\Search\ObjectSearch;
use Ms3\Ms3CommerceFx\Search\SearchContext;
use Ms3\Ms3CommerceFx\ViewHelpers\AbstractTagBasedViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class FormViewHelper extends AbstractTagBasedViewHelper
{

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('pageUid', 'int', 'Page UID where to send AJAX requests to', false);
        $this->registerArgument('root', 'mixed', '', false);
        $this->registerArgument('controlObjectName', 'string', 'Name of Form Controller JS-Object Variable', false);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        /** @var ObjectSearch $search */
        $search = GeneralUtility::makeInstance(ObjectSearch::class);
        $context = SearchContext::createContext();

        try {
            $content = $renderChildrenClosure();

            if (!isset($arguments['id'])) {
                $arguments['id'] = $context->getFormId();
            } else {
                $context->setFormId($arguments['id']);
            }

            if (isset($arguments['controlObjectName'])) {
                $ctrlName = $arguments['controlObjectName'];
            } else {
                $ctrlName = 'ms3Control';
            }

            $pageUid = (isset($arguments['pageUid']) && (int)$arguments['pageUid'] > 0) ? (int)$arguments['pageUid'] : null;
            $settings = self::getSettings($renderingContext);

            $rootId = 0;
            if (isset($arguments['root'])) {
                $rootId = $arguments['root']->getMenuId();
            }

            $content .= self::initForm($search, $context, $rootId, $settings, $ctrlName);

            parent::registerTagArgument('action');
            $arguments['action'] = self::getFormUri($pageUid, $rootId);

            return parent::renderTag('form', $content, $arguments);
        } finally {
            $search->cleanupSearch($context);
            SearchContext::destroyContext();
        }
    }

    private static function getFormUri($pid, $rootId) {
        /** @var ObjectManager $objManager */
        $objManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $objManager->get(UriBuilder::class);
        $pid = $pid ?: $GLOBALS['TSFE']->id;
        $uriBuilder
            ->reset()
            ->setTargetPageUid($pid)
            ->setTargetPageType(159)
            ->setNoCache(true)
            ;

        if ($rootId)
            $uriBuilder->setArguments(['tx_ms3commercefx_pi1[rootId]' => $rootId]);

        return $uriBuilder
            ->build();

    }

    private static function initForm(ObjectSearch $search, SearchContext $context, $rootId, $settings, $ctrlName)
    {
        $filterData = '[]';
        if ($settings['initializeStaticResult']) {
            $filters = $context->getRegisteredFilters();
            $filterAttrs = array_map(function($f) { return $f['attribute']->getName(); }, $filters);
            $multiAttrs = array_filter(array_map(function($f) {return $f['multi'] ? $f['attribute']->getName() : null;},$filters));
            $filterValues = $search->getAvailableFilterValues($context, $rootId, $filterAttrs, $multiAttrs, $settings['sortFilterValues']);
            $filterData = json_encode($filterValues);
        }

        $init = new \stdClass();
        $init->resultElement = $context->getResultElementId();
        $initData = json_encode($init);

        $script = /** @lang JavaScript */<<<XXX
var $ctrlName = null;
jQuery(document).ready(function() {
    $ctrlName = new Ms3CAjaxSearchController('{$context->getFormId()}');
    $ctrlName.init($initData);
    $ctrlName.initializeFilters($filterData);
});
XXX;
        $t = new TagBuilder('script', $script);
        $t->addAttribute('type', 'text/javascript');
        return $t->render();
    }

    private static function getSettings(RenderingContextInterface $renderingContext)
    {
        return $renderingContext->getVariableProvider()->getByPath('settings.ajaxSearch');
    }
}
