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