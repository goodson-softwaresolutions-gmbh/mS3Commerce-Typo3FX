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
    private $includeUsageTypeIds = [];

    /**
     * @param int[]|string $usageTypeIds
     */
    public function setIncludeUsageTypeIds($usageTypeIds) {
        if (!is_array($usageTypeIds)) {
            $usageTypeIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $usageTypeIds);
        }
        $this->includeUsageTypeIds = array_map(intval, $usageTypeIds);
    }

    public function getIncludeUsageTypeIds() {
        return $this->includeUsageTypeIds;
    }
}
