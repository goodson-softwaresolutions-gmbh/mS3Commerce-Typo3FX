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

/**
 * Class Product
 * Represents a Product
 * @package Ms3\Ms3CommerceFx\Domain\Model
 */
class Product extends PimObject
{
    /** @var Price[] */
    protected $prices = null;

    /** @var ProductAvailability[] */
    protected $availability = null;

    public function __sleep()
    {
        $data = parent::__sleep();
        $data = array_flip($data);
        unset($data['prices']);
        unset($data['availability']);
        return array_flip($data);
    }

    public function __wakeup()
    {
        parent::__wakeup();
        $this->prices = null;
        $this->availability = null;
    }

    /**
     * @return int
     */
    public function getEntityType(): int
    {
        return PimObject::TypeProduct;
    }

    public function getBasketQuantity() {
        $cart = $this->getRepo()->getTxCartsBasket();
        if (!$cart) return null;
        $product = $cart->getProduct('mS3Commerce_'.$this->id);
        if ($product) {
            return $product->getQuantity();
        }

        return 0;
    }

    /**
     * @return Price[]|null
     */
    public function getPrices() {
        $this->getRepo()->loadObjectPrices($this);
        if (empty($this->prices)) {
            return null;
        }

        return $this->prices;
    }

    /**
     * @return Price|null
     */
    public function getPrice() {
        $this->getRepo()->loadObjectPrices($this);
        if (empty($this->prices)) {
            return null;
        }

        return $this->prices[0];
    }

    public function pricesLoaded() {
        return $this->prices != null;
    }

    /**
     * @return ProductAvailability[]|null
     */
    public function getAvailabilities() {
        $this->getRepo()->loadObjectAvailability($this);
        if (empty($this->availability)) {
            return null;
        }

        return $this->availability;
    }

    /**
     * @return ProductAvailability|null
     */
    public function getAvailability() {
        $this->getRepo()->loadObjectAvailability($this);
        if (empty($this->availability)) {
            return null;
        }

        return $this->availability[0];
    }

    public function availabilityLoaded() {
        return $this->availability != null;
    }
}
