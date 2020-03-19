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

namespace Ms3\Ms3CommerceFx\Persistence;

class QuerySettings implements \TYPO3\CMS\Core\SingletonInterface
{
    /** @var int */
    private $shopId = 0;
    /** @var int */
    private $marketId = 0;
    /** @var int */
    private $languageId = 0;

    /** @var int[] */
    private $includeUsageTypeIds = [];
    /** @var string */
    private $marketRestrictionAttr = null;
    /** @var string */
    private $userRestrictionAttr = null;
    /** @var string[] */
    private $marketRestrictionValues = null;
    /** @var string */
    private $priceMarket = null;

    /**
     * Sets the shop specific ids
     * @param $shopId
     * @param $marketId
     * @param $languageId
     */
    public function setShopData($shopId, $marketId, $languageId) {
        $this->shopId = $shopId;
        $this->marketId = $marketId;
        $this->languageId = $languageId;
    }

    public function getShopId() {
        return $this->shopId;
    }

    public function getMarketId() {
        return $this->marketId;
    }

    public function getLanguageId() {
        return $this->languageId;
    }

    /**
     * Sets a filter for object usage types. Given as int[] or as ',' separated list
     * @param int[]|string $usageTypeIds The usage type ids to use
     */
    public function setIncludeUsageTypeIds($usageTypeIds) {
        if (!is_array($usageTypeIds)) {
            $usageTypeIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $usageTypeIds);
        }
        $this->includeUsageTypeIds = array_map('intval', $usageTypeIds);
    }

    /**
     * @return int[] The usage type ids to use
     */
    public function getIncludeUsageTypeIds() {
        return $this->includeUsageTypeIds;
    }

    /**
     * Sets a market restriction for objects
     * @param string $attribute The filter attribute
     * @param string[]|string $values The allowed values. Either string[] or ';' separated strings
     */
    public function setMarketRestriction($attribute, $values) {
        $this->marketRestrictionAttr = $attribute;
        if (!is_array($values)) {
            $values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $values);
        }
        $this->marketRestrictionValues = array_filter($values);
    }

    /**
     * @return bool If market restriction is activated
     */
    public function isMarketRestricted() : bool{
        return !empty($this->marketRestrictionAttr) && !empty($this->marketRestrictionValues);
    }

    /**
     * @return string The market restriction attribute
     */
    public function getMarketRestrictionAttribute() {
        return $this->marketRestrictionAttr;
    }

    /**
     * @return string[] The allowed values for market restriction
     */
    public function getMarketRestrictionValues() {
        return $this->marketRestrictionValues;
    }

    /**
     * Sets a user restriction for objects
     * @param string $attribute The user restriction attribute
     */
    public function setUserRestriction($attribute) {
        $this->userRestrictionAttr = $attribute;
    }

    /**
     * @return bool If user restriction is activated
     */
    public function isUserRestricted() : bool {
        return !empty($this->userRestrictionAttr);
    }

    /**
     * @return string The user restriction attribute
     */
    public function getUserRestrictionAttribute() {
        return $this->userRestrictionAttr;
    }

    public function setPriceMarket($market) {
        $this->priceMarket = $market;
    }

    public function getPriceMarket() {
        return $this->priceMarket;
    }
}
