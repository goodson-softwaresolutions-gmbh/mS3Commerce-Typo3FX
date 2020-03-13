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

namespace Ms3\Ms3CommerceFx\Domain\Model;

use Ms3\Ms3CommerceFx\Service\NumberFormatter;

class Price extends AbstractEntity
{
    /** @var string */
    protected $productGuid;
    /** @var string */
    protected $user;
    /** @var string */
    protected $market;
    /** @var string */
    protected $vpe;
    /** @var int */
    protected $startQty;
    /** @var double */
    protected $price;

    public function __construct(int $id)
    {
        parent::__construct($id);
    }

    public function __toString()
    {
        return NumberFormatter::defaultFormat($this->price);
    }

    /**
     * @return string
     */
    public function getProductGuid()
    {
        return $this->productGuid;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getMarket()
    {
        return $this->market;
    }

    /**
     * @return string
     */
    public function getVpe()
    {
        return $this->vpe;
    }

    /**
     * @return int
     */
    public function getStartQty()
    {
        return $this->startQty;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    public function _map($row, $prefix) {
        $this->vpe = $row[$prefix.'VPE'];
        return false;
    }
}
