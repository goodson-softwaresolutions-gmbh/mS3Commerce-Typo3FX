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
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class FormViewHelper extends AbstractTagBasedViewHelper
{

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

            $settings = $renderingContext->getVariableProvider()->getByPath('settings.ajaxSearch');
            $content .= self::initForm($search, $context, $settings);

            return parent::renderTag('div', $content, $arguments);
        } finally {
            $search->cleanupSearch($context);
            SearchContext::destroyContext();
        }
    }

    private static function initForm(ObjectSearch $search, SearchContext $context, $settings)
    {
        $script = '';
        if ($settings['initializeStaticResult']) {
            $filters = $context->getRegisteredFilters();
            $filterAttrs = array_map(function($f) { return $f['attribute']->getName(); }, $filters);


            $filterValues = $search->getFilterValues($context, $filterAttrs);


            $script = json_encode($filterValues);
        }


        $t = new TagBuilder('script', "/*$script*/");
        $t->addAttribute('type', 'text/javascript');
        return $t->render();
    }

    private static function getSettings(RenderingContextInterface $renderingContext)
    {
        return $renderingContext->getVariableProvider()->getByPath('settings.ajaxSearch');
    }
}
