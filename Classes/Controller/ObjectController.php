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

use Ms3\Ms3CommerceFx\Domain\Model\AttributeAccess;
use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;

use Ms3\Ms3CommerceFx\Search\ObjectSearch;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class ObjectController extends AbstractController
{
    private $search;
    public function injectSearch(ObjectSearch $search) {
        $this->search = $search;
    }

    /**
     * @param int $rootId
     * @param string $rootGuid
     * @param int $start
     */
    public function listAction($rootId = 0, $rootGuid = '', $start = 0)
    {
        if ($rootGuid) {
            $obj = $this->repo->getOBjectByMenuGuid($rootGuid);
        } else {
            if ($rootId == 0) $rootId = $this->rootId;
            $obj = $this->repo->getObjectByMenuId($rootId);
        }
        $this->view->assign('object', $obj);
        $this->view->assign('allAttributes', new AttributeAccess($this->repo->getAttributeRepository()));
        $this->view->assign('start', $start);
    }

    /**
     * @param int $rootId
     */
    public function detailAction($rootId)
    {
        $obj = $this->repo->getObjectByMenuId($rootId);
        $this->view->assign('object', $obj);
        $this->view->assign('allAttributes', new AttributeAccess($this->repo->getAttributeRepository()));
    }
}
