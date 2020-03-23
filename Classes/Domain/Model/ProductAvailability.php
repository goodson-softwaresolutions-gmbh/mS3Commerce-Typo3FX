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

class ProductAvailability extends AbstractEntity
{
    /** @var string */
    protected $productGuid;
    /** @var string */
    protected $market;
    /** @var string */
    protected $availability;

    public function __construct(int $id)
    {
        parent::__construct($id);
    }

    public function __toString()
    {
        return $this->availability;
    }

    /**
     * @return string
     */
    public function getProductGuid(): string
    {
        return $this->productGuid;
    }

    /**
     * @return string
     */
    public function getMarket(): string
    {
        return $this->market;
    }

    /**
     * @return string
     */
    public function getAvailability(): string
    {
        return $this->availability;
    }
}