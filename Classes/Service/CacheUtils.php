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

namespace Ms3\Ms3CommerceFx\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheUtils
{
    public const T3CACHE_TAG = 'ms3commerce';

    public static function markPageForT3Cache()
    {
        if (isset($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE']->addCacheTags([self::T3CACHE_TAG]);
        }
    }

    public static function cleanT3Cache()
    {
        /** @var CacheManager $cm */
        $cm = GeneralUtility::makeInstance(CacheManager::class);
        $cm->flushCachesInGroupByTags('pages', [self::T3CACHE_TAG]);
    }

    public static function cleanT3CacheExternal($classLoader)
    {
        \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run(0, \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_FE);
        \TYPO3\CMS\Core\Core\Bootstrap::init($classLoader);
        self::cleanT3Cache();
    }

}
