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
        if (array_key_exists('rootId', $this->settings)) {
            $this->rootId = $this->settings['rootId'];
        }
        if (array_key_exists('templateFile', $this->settings)) {
            $this->defaultViewObjectName = StandaloneView::class;
        }
        if (array_key_exists('includeUsageTypes', $this->settings)) {
            $this->repo->getQuerySettings()->setIncludeUsageTypeIds($this->settings['includeUsageTypes']);
        }
        if (array_key_exists('marketRestriction', $this->settings)) {
            $vals = $this->settings['marketRestriction'];
            if (array_key_exists('attribute', $vals) && array_key_exists('values', $vals)) {
                $this->repo->getQuerySettings()->setMarketRestriction($vals['attribute'], $vals['values']);
            }
        }
        if (array_key_exists('userRestriction', $this->settings)) {
            $vals = $this->settings['userRestriction'];
            if (array_key_exists('attribute', $vals)) {
                $this->repo->getQuerySettings()->setUserRestriction($vals['attribute']);
            }
        }
    }

    public function initializeView(ViewInterface $view)
    {
        if (array_key_exists('templateFile', $this->settings)) {
            $view->setTemplatePathAndFilename($this->settings['templateFile']);
        }
    }
}
