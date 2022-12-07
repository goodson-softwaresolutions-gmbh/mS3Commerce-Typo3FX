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

use Ms3\Ms3CommerceFx\Domain\Model\Product;
use Ms3\Ms3CommerceFx\Domain\Model\ProductAvailability;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;

class AvailabilityRepository extends RepositoryBase
{
    /**
     * @param Product[] $products
     */
    public function loadAvailability($products)
    {
        $products = array_filter($products, function($p) { return !$p->availabilityLoaded(); });
        if (empty($products)) return;

        /** @var Product[] $productMap */
        $productMap = GeneralUtilities::toDictionary($products, function($p) { return $p->getGuid(); });
        $guids = array_keys($productMap);

        $q = $this->_q();
        $q->select('*, ProductAsimOID AS ProductGuid')
            ->from('ShopAvailability')
            ->where($q->expr()->in('ProductAsimOID', $q->createNamedParameter($guids, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)))
            ->orderBy('ProductAsimOID');

        $market = $this->querySettings->getPriceMarket();
        if (!empty($market)) {
            $q->andWhere($q->expr()->eq('Market', $q->createNamedParameter($market)));
        }

        $res = $q->execute();
        $curAvails = [];
        /** @var Product $curProduct */
        $curProduct = null;
        while ($row = $res->fetch()) {
            if (!$curProduct) {
                $curProduct = $productMap[$row['ProductAsimOID']];
            }

            if ($curProduct->getGuid() != $row['ProductAsimOID'] && !empty($curAvails)) {
                $curProduct->_setProperty('availability', $curAvails);

                $curProduct = $productMap[$row['ProductAsimOID']];
                $curAvails = [];
            }

            $avail = new ProductAvailability($row['Id']);
            $this->mapper->mapObject($avail, $row);
            $curAvails[] = $avail;
        }

        // Set availability of last product
        if ($curProduct && !empty($curAvails)) {
            $curProduct->_setProperty('availability', $curAvails);
        }

        // Make empty array for all products without availability (don't trigger loading again)
        foreach ($productMap as $p) {
            if (!$p->availabilityLoaded()) {
                $p->_setProperty('availability', []);
            }
        }
    }
}
