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

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int The entity's ID
     */
    public function getId() : int { return $this->id; }

    public function _getProperties() {
        $properties = get_object_vars($this);
        foreach ($properties as $name => $value) {
            if ($name[0] === '_') {
                unset($properties[$name]);
            } else if ($name == 'id') {
                unset($properties[$name]);
            }
        }
        return $properties;
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
}
