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

namespace Ms3\Ms3CommerceFx\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

class AbstractTagBasedViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    protected $escapeOutput = false;
    protected $escapeChildren = false;

    private static $_tagArgs = [
        'class',
        'dir',
        'id',
        'lang',
        'style',
        'title',
        'accesskey',
        'tabindex',
        'onclick',
        'data'
    ];

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
    }

    public function render()
    {
        $c = function() { return $this->renderChildren(); };
        return static::renderStatic($this->arguments, $c, $this->renderingContext);
    }

    protected static function registerTagArgument($name)
    {
        self::$_tagArgs[] = $name;
    }

    /**
     * @param array $variables
     * @return StandaloneView
     */
    protected static function getView($variables)
    {
        $mgm = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var StandaloneView $view */
        $view = $mgm->get(StandaloneView::class);
        if (is_array($variables)) {
            $view->assignMultiple($variables);
        }
        return $view;
    }

    /**
     * @param string $fileName
     * @param RenderingContextInterface $renderingContext
     * @param array $variables
     * @return StandaloneView
     */
    protected static function getPartialView($fileName, RenderingContextInterface $renderingContext, $variables = null) {
        $file = $renderingContext->getTemplatePaths()->getPartialPathAndFilename($fileName);
        $view = self::getView($variables);
        $view->setTemplatePathAndFilename($file);
        /** @var  \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $ctx */
        $ctx = $view->getRenderingContext()->getControllerContext();
        $ctx->setRequest($renderingContext->getControllerContext()->getRequest());
        $view->assign('settings', $renderingContext->getVariableProvider()->get('settings'));
        return $view;
    }

    /**
     * @param string $fileName
     * @param RenderingContextInterface $renderingContext
     * @param array $variables
     * @return StandaloneView|false
     */
    protected static function tryGetPartialView($fileName, RenderingContextInterface $renderingContext, $variables = null) {
        try {
            return self::getPartialView($fileName, $renderingContext, $variables);
        } catch (InvalidTemplateResourceException $e) {
            return false;
        }
    }

    protected static function renderTag($tagName, $content, $arguments)
    {
        $tag = new TagBuilder($tagName, $content);

        $tagArgs = [];
        foreach ($arguments as $k => $v) {
            if (array_keys(self::$_tagArgs, $k)) {
                $tagArgs[$k] = $v;
            }
        }

        $tagArgs = array_filter($tagArgs);
        $tag->addAttributes($tagArgs);
        return $tag->render();
    }
}