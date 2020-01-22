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

use Ms3\Ms3CommerceFx\Domain\Model\PaginationInfo;
use Ms3\Ms3CommerceFx\Search\SearchContext;
use Ms3\Ms3CommerceFx\Search\ObjectSearch;
use Ms3\Ms3CommerceFx\ViewHelpers\AbstractTagBasedViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class ResultViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('resultTemplate', 'string', '', true);
        $this->registerArgument('root', 'mixed', '', false);
        $this->registerArgument('variables', 'array', '', false);
        $this->registerArgument('start', 'int', false);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $content = self::renderContent($arguments, $renderChildrenClosure, $renderingContext);
        return parent::renderTag('div', $content, $arguments);
    }

    private static function renderContent(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $settings = self::getSettings($renderingContext);

        $view = static::getPartialView($arguments['resultTemplate'], $renderingContext, $arguments['variables']);

        $rootId = 0;
        if (isset($arguments['root'])) {
            $rootId = $arguments['root']->getMenuId();
            $view->assign('root', $arguments['root']);
        }

        if ($settings['initializeStaticResult']) {
            /** @var ObjectSearch $search */
            $search = GeneralUtility::makeInstance(ObjectSearch::class);
            $context = SearchContext::currentContext();
            $structureElement = '';
            $limit = -1;
            $start = 0;

            if (!empty($settings['resultStructureElement'])) {
                $structureElement = $settings['resultStructureElement'];
            }

            if (!empty($settings['pageSize'])) {
                $limit = intval($settings['pageSize']);
            }
            if (!empty($arguments['start'])) {
                $start = intval($arguments['start']) - 1;
                if ($start < 0) $start = 0;
            }

            $search->initObjectSearch($context);
            $search->addSearchObjects($context, $rootId);
            $searchObjects = $search->consolidateObjects($context, $structureElement, $start, $limit);
            $total = $search->getConsolidatedMatchCount($context, $structureElement);

            $page = new PaginationInfo($start + 1, count($searchObjects), $limit, $total);

            $searchResult = [
                'objects' => $searchObjects,
                'page' => $page
            ];

            $view->assign('result', $searchResult);
        }

        return $view->render();
    }

    private static function getSettings(RenderingContextInterface $renderingContext)
    {
        return $renderingContext->getVariableProvider()->getByPath('settings.ajaxSearch');
    }
}
