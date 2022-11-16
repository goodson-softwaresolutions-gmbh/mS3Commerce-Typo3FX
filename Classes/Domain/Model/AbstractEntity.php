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

use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractEntity
 * Basic entity stored in mS3 Commerce database
 * @package Ms3\Ms3CommerceFx\Domain\Model
 */
abstract class AbstractEntity
{
    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var RepositoryFacade $repo
     */
    private $repo;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function __sleep()
    {
        $items = $this->_getProperties();
        unset($items['repo']);
        return array_merge(['id'], array_keys($items));
    }

    public function __wakeup()
    {
        $this->repo = null;
    }

    /**
     * @return int The entity's ID
     */
    public function getId() : int { return $this->id; }

    private static $s_propertiesCache = [];
    public function _getProperties() {
        if (!array_key_exists(get_class($this), self::$s_propertiesCache)) {
            $properties = get_object_vars($this);
            foreach ($properties as $name => $value) {
                if ($name[0] === '_') {
                    unset($properties[$name]);
                } else if ($name == 'id') {
                    unset($properties[$name]);
                }
            }
            self::$s_propertiesCache[get_class($this)] = $properties;
        }

        return self::$s_propertiesCache[get_class($this)];
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0 && empty($arguments)) {
            $prop = lcfirst(substr($name,3));
            // TODO: Exclude properties
            if (property_exists($this, $prop)) {
                return $this->_getProperty($prop);
            }
            if (strpos($prop, 'is') === 0 && method_exists($this, $prop)) {
                return $this->$prop();
            }
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            // TODO: Exclude properties
            return $this->_getProperty($name);
        }
        return null;
    }

    public function _getProperty($name) {
        return $this->$name;
    }

    public function _setProperty($name, $value) {
        $this->$name = $value;
    }

    protected function getRepo() {
        if ($this->repo == null) {
            $this->repo = RepositoryFacade::getInstance();
        }
        return $this->repo;
    }

    public function _map($row, $prefix) {
        return false;
    }
}
