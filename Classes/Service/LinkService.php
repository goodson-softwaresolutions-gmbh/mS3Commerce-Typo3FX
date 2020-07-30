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

namespace Ms3\Ms3CommerceFx\Service;

use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class LinkService implements SingletonInterface
{
    /** @var PimObjectRepository */
    private $repo;
    /**
     * @param PimObjectRepository $repo
     */
    public function injectObjectRepository(PimObjectRepository $repo) {
        $this->repo = $repo;
    }

    /** @var UriBuilder */
    private $builder;
    /**
     * @param UriBuilder $builder
     */
    public function injectUriBuilder(UriBuilder $builder) {
        $this->builder = $builder;
    }

    /**
     * Builds a uri for an object
     * @param PimObject $object The object to link to
     * @param array $settings Linker settings (e.g. default pid, use GUID Links, ...)
     * @param array $additionalArguments Additional arguments (e.g. real pid, page type, additional parameters, ...)
     * @return string The uri
     */
    public function buildObjectUri($object, $settings, $additionalArguments = [])
    {
        $args = $this->addArgumentsForUriBuilding($object, $settings, $additionalArguments);
        return $this->buildUri($args);
    }

    /**
     * Adds object specific arguments to the given argument array. The original array is not modified
     * @param PimObject $object The object to link to
     * @param array $settings Linker settings (e.g. default pid, use GUID Links, ...)
     * @param array $arguments Additional arguments (e.g. real pid, page type, additional parameters, ...)
     * @return array The modified arguments
     */
    public function addArgumentsForUriBuilding($object, $settings, $arguments = [])
    {
        if (!isset($arguments['pageUid']) && array_key_exists('pid', $settings)) {
            $pid = $settings['pid'];
            // TODO: Level dependent overrides
            if ($pid) {
                $arguments['pageUid'] = $pid;
            }
        }

        $params = isset($arguments['additionalParams']) ? $arguments['additionalParams'] : [];
        if ($settings['byGuid'] == 1) {
            $id = $object->getMenuId();
            if ($id) {
                $menu = $this->repo->getMenuById($id);
                $params['tx_ms3commercefx_pi1']['rootGuid'] = $menu->getContextID();
            }
        } else {
            $params['tx_ms3commercefx_pi1']['rootId'] = $object->getMenuId();
        }

        $arguments['additionalParams'] = $params;
        return $arguments;
    }

    private function buildUri($arguments)
    {
        /** @see \TYPO3\CMS\Fluid\ViewHelpers\Uri\PageViewHelper */
        $pageUid = $arguments['pageUid'];
        $additionalParams = $arguments['additionalParams'];
        $pageType = $arguments['pageType'];
        $noCache = $arguments['noCache'];
        $noCacheHash = $arguments['noCacheHash'];
        $section = $arguments['section'];
        $linkAccessRestrictedPages = $arguments['linkAccessRestrictedPages'];
        $absolute = $arguments['absolute'];
        $addQueryString = $arguments['addQueryString'];
        $argumentsToBeExcludedFromQueryString = $arguments['argumentsToBeExcludedFromQueryString'] ?? [];
        $addQueryStringMethod = $arguments['addQueryStringMethod'] ?? [];

        $uri = $this->builder
            ->setTargetPageUid($pageUid)
            ->setTargetPageType($pageType)
            ->setNoCache($noCache)
            ->setUseCacheHash(!$noCacheHash)
            ->setSection($section)
            ->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
            ->setArguments($additionalParams)
            ->setCreateAbsoluteUri($absolute)
            ->setAddQueryString($addQueryString)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
            ->setAddQueryStringMethod($addQueryStringMethod)
            ->build();
        return $uri;
    }
}
