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

use Ms3\Ms3CommerceFx\Domain\Model\AbstractEntity;

/**
 * Class DataMapper
 * Basic mapper from database rows to objects from the mS3 Commerce database
 * @package Ms3\Ms3CommerceFx\Persistence
 */
class DataMapper implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Fills an object from an associative array
     * @param $object AbstractEntity The object to fill
     * @param $row array The properties to fill
     * @param $prefix string Optional prefix of column names
     */
    public function mapObject(AbstractEntity $object, $row, $prefix = '') {
        $props = $object->_getProperties();
        foreach ($row as $key => $value) {
            if (!empty($prefix)) {
                if (strpos($key, $prefix) === 0) {
                    $key = substr($key, strlen($prefix));
                } else {
                    continue;
                }
            }
            if (array_key_exists(lcfirst($key), $props)) {
                $object->_setProperty(lcfirst($key), $value);
            }
        }
    }
}
