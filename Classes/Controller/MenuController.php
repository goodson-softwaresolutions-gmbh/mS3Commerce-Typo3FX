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

use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class MenuController extends AbstractController
{
    public function menuAction()
    {
        if (array_key_exists('startId', $this->settings)) {
            $startId = $this->settings['startId'];
        } else {
            $startId = $this->rootId;
        }
        $obj = $this->repo->getObjectByMenuId($startId);
        $this->view->assign('object', $obj);
    }
}
