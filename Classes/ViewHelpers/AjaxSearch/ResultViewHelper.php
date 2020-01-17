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
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ResultViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;
    protected $escapeChildren = false;

    public function initializeArguments()
    {
        $this->registerArgument('resultTemplate', 'string', '', true);
        $this->registerArgument('root', 'mixed', '', false);
        $this->registerArgument('variables', 'array', '', false);
        $this->registerArgument('start', 'int', false);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $settings = $renderingContext->getVariableProvider()->getByPath('settings.ajaxSearch');

        $view = static::getView();
        $file = $renderingContext->getTemplatePaths()->getPartialPathAndFilename($arguments['resultTemplate']);
        $view->setTemplatePathAndFilename($file);
        if (is_array($arguments['variables'])) {
            $view->assignMultiple($arguments['variables']);
        }

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
            $view->assign('result', $searchResult);
        }

        return $view->render();
    }

    /**
     * @return StandaloneView
     */
    private static function getView()
    {
        $mgm = GeneralUtility::makeInstance(ObjectManager::class);
        return $mgm->get(StandaloneView::class);
    }
}
