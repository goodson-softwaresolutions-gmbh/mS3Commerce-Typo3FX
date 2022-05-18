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

namespace Ms3\Ms3CommerceFx\ViewHelpers\Link;

use Ms3\Ms3CommerceFx\Service\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ObjectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('object', 'mixed', 'Object to link to', true);
    }

    /** @var LinkService */
    private $linker;

    /**
     * @param LinkService $linkerService
     */
    public function injectLinkerService(LinkService $linkerService) {
        $this->linker = $linkerService;
    }

    /**
     * @return LinkService
     */
    private function getLinker() {
        if (!$this->linker) {
            $mgm = GeneralUtility::makeInstance(ObjectManager::class);
            $this->linker = $mgm->get(LinkService::class);
        }
        return $this->linker;
    }

    public function render()
    {
        $object = $this->arguments['object'];
        if (!$object) return "";
        $settings = $this->renderingContext->getVariableProvider()->getByPath('settings.link');
        $uri = $this->getLinker()->buildObjectUri($object, $settings, $this->arguments);

        /** @see \TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper */
        if ((string)$uri !== '') {
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($this->renderChildren());
            $this->tag->forceClosingTag(true);
            $result = $this->tag->render();
        } else {
            $result = $this->renderChildren();
        }
        return $result;
    }

}
