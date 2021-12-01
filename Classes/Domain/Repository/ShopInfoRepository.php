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

use Ms3\Ms3CommerceFx\Domain\Model\ShopInfo;

class ShopInfoRepository extends RepositoryBase
{
    /** @var ShopInfo[] */
    private $shops;

    /**
     * @param int $id
     * @return ShopInfo|null
     */
    public function getById($id)
    {
        $this->loadAll();
        return $this->store->getObjectByIdentifier($id, ShopInfo::class);
    }

    public function getByShopId($shopId)
    {
        $this->loadAll();
        return $this->store->getObjectBySecondaryIdentifier($shopId, ShopInfo::class);
    }

    /**
     * Returns the ShopInfo that contains the given id
     * @param int $id The id to get shop for
     * @return ShopInfo|null The shop
     */
    public function getByContainedId($id)
    {
        $this->loadAll();
        foreach ($this->shops as $shop) {
            if ($shop->containsId($id)) {
                return $shop;
            }
        }
        return null;
    }

    private function loadAll() {
        if ($this->shops) return;

        $this->shops = [];

        $res = $this->_q()
            ->select('*')
            ->from('ShopInfo')
            ->execute()
            ->fetchAllAssociative();
        foreach ($res as $data) {
            $shop = new ShopInfo($data['Id']);
            $this->mapper->mapObject($shop, $data);
            $this->shops[$shop->getId()] = $shop;
            $this->store->registerObject($shop);
            $this->store->registerObjectSecondary($shop, $shop->getShopId());
        }
    }
}
