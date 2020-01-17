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

namespace Ms3\Ms3CommerceFx\Search;

use Ms3\Ms3CommerceFx\Domain\Model\PaginationInfo;
use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;
use Ms3\Ms3CommerceFx\Domain\Repository\SearchRepository;
use Ms3\Ms3CommerceFx\Service\RestrictionService;
use TYPO3\CMS\Core\SingletonInterface;

class ObjectSearch implements SingletonInterface
{
    /** @var RepositoryFacade */
    private $repo;

    /**
     * @param RepositoryFacade $repo
     */
    public function injectRepository(RepositoryFacade $repo) {
        $this->repo = $repo;
    }

    public function searchObjects(array $criteria)
    {
        if (isset($criteria['rootId'])) {
            $menuIds = $this->repo->getSearchRepository()->findInMenuId($criteria['rootId'], $criteria['structureElement']);
        } else {
            $menuIds = $this->repo->getSearchRepository()->findStructureElements($criteria['structureElement']);
        }

        $objects = $this->repo->getObjectsByMenuIds($menuIds);

        $page = new PaginationInfo();

        $page->setTotal(count($objects));
        if ($criteria['start'] > 0) {
            $page->setStart($criteria['start']);
            $objects = array_slice($objects, $page->getStart()-1);
        } else {
            $page->setStart(1);
        }
        if ($criteria['limit'] > 0) {
            $objects = array_slice($objects, 0, $criteria['limit']);
            $page->setPageSize($criteria['limit']);
        }
        $page->setCount(count($objects));

        return [
            'menuIds' => $menuIds,
            'objects' => $objects,
            'page' => $page
        ];
    }
}
