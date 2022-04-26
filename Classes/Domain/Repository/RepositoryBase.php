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

namespace Ms3\Ms3CommerceFx\Domain\Repository;

class RepositoryBase implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \Ms3\Ms3CommerceFx\Persistence\DbBackend
     */
    protected $db;

    /**
     * @var \Ms3\Ms3CommerceFx\Persistence\StorageSession
     */
    protected $store;

    /**
     * @var \Ms3\Ms3CommerceFx\Persistence\DataMapper
     */
    protected $mapper;

    /**
     * @var \Ms3\Ms3CommerceFx\Persistence\QuerySettings
     */
    protected $querySettings;

    /**
     * @var \Ms3\Ms3CommerceFx\Service\ShopService
     */
    protected $shopService;

    /** @var \Ms3\Ms3CommerceFx\Service\ObjectCreationService */
    protected $objectCreation;

    /**
     * @param \Ms3\Ms3CommerceFx\Persistence\DbBackend $backend
     */
    public function injectDbBackend(\Ms3\Ms3CommerceFx\Persistence\DbBackend $backend)
    {
        $this->db = $backend;
    }

    /**
     * @param \Ms3\Ms3CommerceFx\Persistence\StorageSession $session
     */
    public function injectStorageSession(\Ms3\Ms3CommerceFx\Persistence\StorageSession $session)
    {
        $this->store = $session;
    }

    /**
     * @param \Ms3\Ms3CommerceFx\Persistence\DataMapper $mapper
     */
    public function injectDataMapper(\Ms3\Ms3CommerceFx\Persistence\DataMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @param \Ms3\Ms3CommerceFx\Persistence\QuerySettings $settings
     */
    public function injectQuerySettings(\Ms3\Ms3CommerceFx\Persistence\QuerySettings $settings) {
        $this->querySettings = $settings;
    }

    /**
     * @param \Ms3\Ms3CommerceFx\Service\ShopService $shopService
     */
    public function injectShopService(\Ms3\Ms3CommerceFx\Service\ShopService $shopService) {
        $this->shopService = $shopService;
    }

    /**
     * @param ObjectCreationService $ocs
     */
    public function injectObjectCreationService(\Ms3\Ms3CommerceFx\Service\ObjectCreationService $ocs) {
        $this->objectCreation = $ocs;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->_q();
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function _q()
    {
        return $this->db->getConnection()->createQueryBuilder();
    }
}
