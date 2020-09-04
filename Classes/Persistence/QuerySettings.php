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
    /** @var string[] */
    private $marketRestrictionAttrs = [];
    /** @var string[][] */
    private $marketRestrictionValues = [];
    /** @var string */
    private $userRestrictionAttr = null;
    /** @var string[] */
    private $userRestrictionDefaultValues = [];
    /** @var string[] */
    private $userRestrictionNotLoggedInValues = [];
    /** @var string */
    private $priceMarket = null;
    /** @var int */
    private $txCartBasketPid = null;

    /**
     * @return int
     */
    public function getTxCartBasketPid(): int
    {
        return $this->txCartBasketPid;
    }

    /**
     * @param int $txCartBasketPid
     */
    public function setTxCartBasketPid(int $txCartBasketPid): void
    {
        $this->txCartBasketPid = $txCartBasketPid;
    }

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

    public function initializeFromSettings($settings) {
        if (!empty($settings['includeUsageTypes'])) {
            $this->setIncludeUsageTypeIds($settings['includeUsageTypes']);
        }
        if (array_key_exists('marketRestriction', $settings)) {
            $vals = $settings['marketRestriction'];
            if (!empty($vals['attribute']) && !empty($vals['values'])) {
                $this->setMarketRestriction($vals['attribute'], $vals['values']);
            }
            if (array_key_exists('levels', $vals)) {
                foreach ($vals['levels'] as $level) {
                    if (!empty($level['attribute']) && !empty($level['values'])) {
                        $this->setMarketRestriction($level['attribute'], $level['values'], $level['name']);
                    }
                }
            }
        }
        if (array_key_exists('userRestriction', $settings)) {
            $vals = $settings['userRestriction'];
            if (!empty($vals['attribute'])) {
                $this->setUserRestriction($vals['attribute'], $vals['defaultValues'], $vals['notLoggedInValues']);
            }
        }
        if (array_key_exists('priceMarket', $settings)) {
            $this->setPriceMarket($settings['priceMarket']);
        }
        if (array_key_exists('tx_cart', $settings)) {
            $this->setTxCartBasketPid($settings['tx_cart']['basketPid']);
        }
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
     * @param string $level The restricted level. If empty, setting for all unspecified levels
     */
    public function setMarketRestriction($attribute, $values, $level = null) {
        if (!is_array($values)) {
            $values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $values);
        }
        if (empty($values)) return;
        if (empty($level)) $level = 0;
        $this->marketRestrictionAttrs[$level] = $attribute;
        $this->marketRestrictionValues[$level] = $values;
    }

    /**
     * @return bool If market restriction is activated
     */
    public function isMarketRestricted() : bool {
        return !empty($this->marketRestrictionAttrs);
    }

    /**
     * Gets the restriction attribute for the given structure element
     * @param string $structureElementName The structure element name
     * @return string The restriction attribute
     */
    public function getStructureElementRestrictionAttribute($structureElementName) {
        if (array_key_exists($structureElementName, $this->marketRestrictionAttrs))
            return $this->marketRestrictionAttrs[$structureElementName];
        return $this->marketRestrictionAttrs[0];
    }

    /**
     * @return string[] All market restriction attributes
     */
    public function getMarketRestrictionAttributes() {
        return array_values($this->marketRestrictionAttrs);
    }

    /**
     * Gets the restriction values for the given structure element
     * @param string $structureElementName The structure element name
     * @return string[] The restriction values
     */
    public function getStructureElementRestrictionValues($structureElementName) {
        if (array_key_exists($structureElementName, $this->marketRestrictionValues))
            return $this->marketRestrictionValues[$structureElementName];
        return $this->marketRestrictionValues[0];
    }

    /**
     * Sets a user restriction for objects
     * @param string $attribute The user restriction attribute
     * @param $defaultValues
     * @param $notLoggedInValues
     */
    public function setUserRestriction($attribute,$defaultValues,$notLoggedInValues) {
        if (!is_array($defaultValues)) {
            $defaultValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $defaultValues);
        }
        if (!is_array($notLoggedInValues)) {
            $notLoggedInValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $notLoggedInValues);
        }
        $this->userRestrictionAttr = $attribute;
        $this->userRestrictionDefaultValues = $defaultValues;
        $this->userRestrictionNotLoggedInValues = $notLoggedInValues;
    }

    public function getUserRestrictionNotLoggedInValues(){
        return $this->userRestrictionNotLoggedInValues;
    }

    public function getUserRestrictionDefaultValues(){
        return $this->userRestrictionDefaultValues;
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

    /**
     * Sets the market for prices
     * @param $market
     */
    public function setPriceMarket($market) {
        $this->priceMarket = $market;
    }

    /**
     * @return string The market for prices
     */
    public function getPriceMarket() {
        return $this->priceMarket;
    }
}
