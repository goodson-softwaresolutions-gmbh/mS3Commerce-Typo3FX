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
        $settings = $renderingContext->getVariableProvider()->getByPath('settings.ajaxSearch');

        $view = static::getPartialView($arguments['resultTemplate'], $renderingContext, $arguments['variables']);

        if ($settings['initializeStaticResult']) {
            /** @var ObjectSearch $search */
            $search = GeneralUtility::makeInstance(ObjectSearch::class);
            $crit = [];
            if (isset($arguments['root'])) {
                $crit['rootId'] = $arguments['root']->getMenuId();
                $view->assign('root', $arguments['root']);
            }
            if (!empty($settings['resultStructureElement'])) {
                $crit['structureElement'] = $settings['resultStructureElement'];
            }
            if (!empty($settings['pageSize'])) {
                $crit['limit'] = intval($settings['pageSize']);
            }
            if (!empty($arguments['start'])) {
                $crit['start'] = intval($arguments['start']);
            }
            $searchResult = $search->searchObjects($crit);

            SearchContext::currentContext()->registerSearchMenuId($searchResult['menuIds']);
            $view->assign('result', $searchResult);
        }

        return $view->render();
    }


}
