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
    /**
     * Returns the ShopInfo that contains the given id
     * @param int $id The id to get shop for
     * @return ShopInfo|null The shop
     */
    public function getByContainedId($id)
    {
        $q = $this->_q();
        $q->select('*')
            ->from('ShopInfo')
            ->where($q->expr()->andX(
                $q->expr()->lte('StartId', $id),
                $q->expr()->lt($id, 'EndId')
            ));
        $res = $q->execute()->fetch();
        if ($res) {
            $shop = new ShopInfo($res['Id']);
            $this->mapper->mapObject($shop, $res);
            return $shop;
        }
        return null;
    }
}
