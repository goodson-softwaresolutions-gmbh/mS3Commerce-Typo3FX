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

use Doctrine\DBAL\ParameterType;
use Ms3\Ms3CommerceFx\Domain\Model\Price;
use Ms3\Ms3CommerceFx\Domain\Model\Product;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;

class PriceRepository extends RepositoryBase
{
    /**
     * @param Product[] $products
     */
    public function loadPrices($products)
    {
        $products = array_filter($products, function($p) { return !$p->pricesLoaded(); });
        if (empty($products)) return;

        /** @var Product[] $productMap */
        $productMap = GeneralUtilities::toDictionary($products, function($p) { return $p->getGuid(); });
        $guids = array_keys($productMap);

        $q = $this->_q();
        $q->select('*')
            ->from('ShopPrices')
            ->where($q->expr()->in('ProductAsimOID', $q->createNamedParameter($guids, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)))
            ->orderBy('ProductAsimOID')
            ->addOrderBy('VPE')
            ->addOrderBy('StartQty');

        $market = $this->querySettings->getPriceMaket();
        if (!empty($market)) {
            $q->andWhere($q->expr()->eq('Market', $q->createNamedParameter($market)));
        }

        /* TODO USER Restriction*/

        $res = $q->execute();
        $curPrices = [];
        /** @var Product $curProduct */
        $curProduct = null;
        while ($row = $res->fetch()) {
            if (!$curProduct) {
                $curProduct = $productMap[$row['ProductAsimOID']];
            }

            if ($curProduct->getGuid() != $row['ProductAsimOID'] && !empty($curPrices)) {
                $curProduct->_setProperty('prices', $curPrices);

                $curProduct = $productMap[$row['ProductAsimOID']];
                $curPrices = [];
            }

            $price = new Price($row['Id']);
            $this->mapper->mapObject($price, $row);
            $curPrices[] = $price;
        }

        // Set prices of last product
        if ($curProduct && $curProduct->getGuid() != $row['ProductAsimOID'] && !empty($curPrices)) {
            $curProduct->_setProperty('prices', $curPrices);
        }

        // Make empty array for all products without prices (don't trigger loading again)
        foreach ($productMap as $p) {
            if (!$p->pricesLoaded()) {
                $p->_setProperty('prices', []);
            }
        }
    }
}
