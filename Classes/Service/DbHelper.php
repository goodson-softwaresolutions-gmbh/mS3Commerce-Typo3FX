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

use Ms3\Ms3CommerceFx\Persistence\DbBackend;

class DbHelper
{
    private function __construct() {}

    public static function getTableColumnNames($tableName)
    {
        /** @var DbBackend $db */
        $db = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(DbBackend::class);
        $res = $db->getConnection()->executeQuery("DESC $tableName")->fetchAll();
        return array_map(function($r) { return $r['Field']; }, $res);
    }

    public static function getTableColumnAs($tableName, $aliasPrefix, $tableAlias = '')
    {
        if (empty($tableAlias)) $tableAlias = $tableName;
        $cols = self::getTableColumnNames($tableName);
        $ret = [];
        foreach ($cols as $c) {
            $ret[] = "$tableAlias.$c AS {$aliasPrefix}{$c}";
        }
        return implode(',', $ret);
    }
}
