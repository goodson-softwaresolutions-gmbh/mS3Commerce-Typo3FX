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

use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper;

class ObjectViewHelper extends PageViewHelper
{

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('object', 'mixed', 'Object to link to', true);
    }

    public function render()
    {
        /** @var PimObject $object */
        $object = $this->arguments['object'];
        $settings = $this->renderingContext->getVariableProvider()->getByPath('settings.link');
        if (!isset($this->arguments['pageUid']) && array_key_exists('pid', $settings)) {
            $pid = $settings['pid'];
            // TODO: Level dependent overrides
            if ($pid) {
                $this->arguments['pageUid'] = $pid;
            }
        }

        $params = isset($this->arguments['additionalParams']) ? $this->arguments['additionalParams'] : [];
        if ($settings['byGuid']) {
            $manager = new ObjectManager();
            /** @var PimObjectRepository $repo */
            $repo = $manager->get(PimObjectRepository::class);
            $menu = $repo->getMenuById($object->getMenuId());
            $params['tx_ms3commercefx_pi1']['rootGuid'] = $menu->getContextID();
        } else {
            $params['tx_ms3commercefx_pi1']['rootId'] = $object->getMenuId();
        }

        $this->arguments['additionalParams'] = $params;

        return parent::render();
    }
}