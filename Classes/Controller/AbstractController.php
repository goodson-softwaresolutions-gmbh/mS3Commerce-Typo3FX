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

abstract class AbstractController extends ActionController
{
    /**
     * @var RepositoryFacade
     */
    protected $repo;

    /**
     * @param \Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade $repository
     */
    public function injectRepository(\Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade $repository)
    {
        $this->repo = $repository;
    }

    protected $rootId = 0;

    public function initializeAction()
    {
        $this->initializeRootId();
        $this->initializeViewTemplate();
        $this->initializeQuerySettings();
    }

    public function initializeView(ViewInterface $view)
    {
        if (!empty($this->settings['templateFile'])) {
            $view->setTemplatePathAndFilename($this->settings['templateFile']);
        }
    }

    protected function initializeRootId() {
        if (!empty($this->settings['rootId'])) {
            $this->rootId = $this->settings['rootId'];
            $this->initializeShopParameters($this->rootId);
        }
    }

    protected function initializeViewTemplate() {
        if (!empty($this->settings['templateFile'])) {
            $this->defaultViewObjectName = StandaloneView::class;
        }
    }

    protected function initializeQuerySettings() {
        if (!empty($this->settings['includeUsageTypes'])) {
            $this->repo->getQuerySettings()->setIncludeUsageTypeIds($this->settings['includeUsageTypes']);
        }
        if (array_key_exists('marketRestriction', $this->settings)) {
            $vals = $this->settings['marketRestriction'];
            if (!empty($vals['attribute'])) {
                $this->repo->getQuerySettings()->setMarketRestriction($vals['attribute'], $vals['values']);
            }
        }
        if (array_key_exists('userRestriction', $this->settings)) {
            $vals = $this->settings['userRestriction'];
            if (!empty($vals['attribute'])) {
                $this->repo->getQuerySettings()->setUserRestriction($vals['attribute']);
            }
        }
    }

    protected function initializeShopParameters($rootId)
    {
        $shopInfo = $this->repo->getShopInfoRepository()->getByContainedId($rootId);
        if ($shopInfo) {
            $this->repo->getQuerySettings()->setShopData($shopInfo->getId(), $shopInfo->getMarketId(), $shopInfo->getLanguageId());
        } else {
            $this->repo->getQuerySettings()->setShopData(0, 0, 0);
        }
    }
}
