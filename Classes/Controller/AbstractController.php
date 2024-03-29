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
use Ms3\Ms3CommerceFx\Service\CacheUtils;
use Ms3\Ms3CommerceFx\Service\NumberFormatter;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;
use Ms3\Ms3CommerceFx\Service\ShopService;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\ErrorController;

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
        CacheUtils::markPageForT3Cache();
    }

    public function initializeView(ViewInterface $view)
    {
        if (!empty($this->settings['templateFile'])) {
            $view->setTemplatePathAndFilename($this->settings['templateFile']);
        }
        if (isset($this->settings['variables']) && is_array($this->settings['variables'])) {
            $this->view->assignMultiple($this->settings['variables']);
        }
    }

    protected function initializeRootId() {
        if (!empty($this->settings['rootId'])) {
            $this->rootId = $this->settings['rootId'];
            $this->initializeShopParameters($this->rootId);
            return;
        }

        $rootGuid = '';
        if (!empty($this->settings['overrideRootGuid'])) {
            $rootGuid = $this->settings['overrideRootGuid'];
        } else if (!empty($this->settings['rootGuid'])) {
            $rootGuid = $this->settings['rootGuid'];
        }


        if (!empty($rootGuid)) {
            if (!ObjectHelper::isShopGuid($rootGuid)) {
                if (isset($this->settings['shopId'])) {
                    $rootGuid = ObjectHelper::createShopGuid($rootGuid, $this->settings['shopId']);
                }
            }
            $m = $this->repo->getObjectRepository()->getMenuByGuid($rootGuid);
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
        if (!$shopInfo) {
            if (!empty($this->settings['shopId'])) {
                $shopInfo = $this->repo->getShopInfoRepository()->getByShopId($this->settings['shopId']);
            }
        }
        $this->repo->getQuerySettings()->setShopData($shopInfo);
    }

    protected function initializeFormats() {
        if (array_key_exists('numberFormat', $this->settings)) {
            NumberFormatter::setDefaultFormat($this->settings['numberFormat']['comma'], $this->settings['numberFormat']['thousands']);
        }
    }

    protected function handleNotFound()
    {
        if ($this->settings['notFoundMode'] == '404') {
            // This will throw and thus terminate
            $this->send404();
        }
        // Nothing to do, continue as normal
    }

    protected function send404() {
        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
            $GLOBALS['TYPO3_REQUEST'],
            ''
        );
        throw new ImmediateResponseException($response, 1591428020);
    }

}
