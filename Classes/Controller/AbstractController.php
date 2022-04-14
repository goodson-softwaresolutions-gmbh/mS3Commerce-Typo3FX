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
use Ms3\Ms3CommerceFx\Service\NumberFormatter;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;
use Ms3\Ms3CommerceFx\Service\ShopService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

abstract class AbstractController extends ActionController
{
    /**
     * @var RepositoryFacade
     */
    protected $repo;

    /**
     * @var ShopService
     */
    protected $shopService;

    /**
     * @param \Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade $repository
     */
    public function injectRepository(\Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade $repository)
    {
        $this->repo = $repository;
    }

    /**
     * @param ShopService $service
     */
    public function injectShopService(ShopService $service)
    {
        $this->shopService = $service;
    }

    protected $rootId = 0;

    public function initializeAction()
    {
        $this->initializeRootId();
        $this->initializeViewTemplate();
        $this->initializeQuerySettings();
        $this->initializeFormats();
    }

    public function initializeView(ViewInterface $view)
    {
        if (!empty($this->settings['templateFile'])) {
            $view->setTemplatePathAndFilename($this->settings['templateFile']);
        }
        if (is_array($this->settings['variables'])) {
            $this->view->assignMultiple($this->settings['variables']);
        }
    }

    protected function initializeRootId() {
        if (!empty($this->settings['rootId'])) {
            $this->rootId = $this->settings['rootId'];
            $this->initializeShopParameters($this->rootId);
        } else if (!empty($this->settings['rootGuid'])) {
            $rg = $this->settings['rootGuid'];
            if (!ObjectHelper::isShopGuid($rg)) {
                if (isset($this->settings['shopId'])) {
                    $rg = ObjectHelper::createShopGuid($rg, $this->settings['shopId']);
                }
            }
            $m = $this->repo->getObjectRepository()->getMenuByGuid($rg);
            if ($m) {
                $this->rootId = $m->getId();
                $this->initializeShopParameters($this->rootId);
            }
        }
    }

    protected function initializeViewTemplate() {
        if (!empty($this->settings['templateFile'])) {
            $this->defaultViewObjectName = StandaloneView::class;
        }
    }

    protected function initializeQuerySettings() {
        $this->repo->getQuerySettings()->initializeFromSettings($this->settings);
    }

    protected function initializeShopParameters($rootId)
    {
        $shopInfo = $this->repo->getShopInfoRepository()->getByContainedId($rootId);
        $this->repo->getQuerySettings()->setShopData($shopInfo);
    }

    protected function initializeFormats() {
        if (array_key_exists('numberFormat', $this->settings)) {
            NumberFormatter::setDefaultFormat($this->settings['numberFormat']['comma'], $this->settings['numberFormat']['thousands']);
        }
    }
}
