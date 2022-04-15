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

use Ms3\Ms3CommerceFx\Service\ObjectHelper;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class MenuController extends AbstractController
{
    /**
     * @param int $rootId
     */
    public function menuAction($rootId = 0)
    {
        $currentObjectId = $rootId;
        $startId = 0;
        if (isset($this->settings['startId'])) {
            $startId = $this->settings['startId'];
        } else if (isset($this->settings['startGuid'])) {
            $guid = ObjectHelper::createShopGuid($this->settings['startGuid'], $this->repo->getQuerySettings()->getShopId());
            $root = $this->repo->getObjectByMenuGuid($guid);
            $startId = $root ? $root->getMenuId() : 0;
        }

        if (!$startId) {
            $startId = $this->rootId;
        }
        $obj = $this->repo->getObjectByMenuId($startId);
        $this->view->assign('object', $obj);
        $cur = $this->repo->getObjectByMenuId($currentObjectId);
        $this->view->assign('current', $cur);
    }
}
