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

    private static $cache = [];

    /**
     * Returns the column names for a table
     * @param string $tableName
     * @return string[]
     */
    public static function getTableColumnNames($tableName)
    {
        if (!array_key_exists($tableName, self::$cache)) {
            /** @var DbBackend $db */
            $db = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(DbBackend::class);
            $res = $db->getConnection()->executeQuery("DESC $tableName")->fetchAll();
            $fieldNames = array_map(function($r) { return $r['Field']; }, $res);
            self::$cache[$tableName] = $fieldNames;
        }
        return self::$cache[$tableName];
    }

    /**
     * Returns a list of column names for a table, suitable for a SELECT statement.
     * A prefix can be added to the column names. Also, an alias for the table name can be given.
     *
     * @param string $tableName The table name
     * @param string $aliasPrefix Optional prefix for column aliases
     * @param string $tableAlias Optional table alias. By default, the table name is used
     * @return string E.g. "table.col1,table.col2" or "t.col1 AS t_col1,t.col2 AS t_col2"
     */
    public static function getTableColumnAs($tableName, $aliasPrefix = '', $tableAlias = '')
    {
        if (empty($tableAlias)) $tableAlias = $tableName;
        $cols = self::getTableColumnNames($tableName);
        $ret = [];
        foreach ($cols as $c) {
            $ret[] = "$tableAlias.$c AS $aliasPrefix$c";
        }
        return implode(',', $ret);
    }
}
