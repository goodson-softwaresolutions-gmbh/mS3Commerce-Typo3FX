<?php

namespace Ms3\Ms3CommerceFx\Controller;

use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;

class ObjectController extends ActionController
{
    /**
     * @var RepositoryFacade
     */
    private $repo;

    /**
     * @param \Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade $repository
     */
    public function injectRepository(\Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade $repository)
    {
        $this->repo = $repository;
    }

    private $rootId = 0;
    public function initializeAction()
    {
        if (isset($this->settings) && array_key_exists('rootId', $this->settings)) {
            $this->rootId = $this->settings['rootId'];
        }
    }

    /**
     * @param int $rootId
     */
    public function listAction($rootId = 0)
    {
        if ($rootId == 0) $rootId = $this->rootId;
        $obj = $this->repo->getObjectByMenuId($rootId);
        $this->view->assign('object', $obj);
    }

    /**
     * @param int $rootId
     */
    public function detailAction($rootId)
    {
        $obj = $this->repo->getObjectByMenuId($rootId);
        $this->view->assign('object', $obj);
    }
}
