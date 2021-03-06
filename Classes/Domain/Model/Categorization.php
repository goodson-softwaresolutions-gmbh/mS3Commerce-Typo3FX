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

use Ms3\Ms3CommerceFx\Service\GeneralUtilities;

class Categorization extends AbstractEntity implements \ArrayAccess
{
    protected $name;
    protected $type;
    /**
     * @var Attribute[]
     */
    protected $attributes = null;

    public function __construct(int $id)
    {
        parent::__construct($id);
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes() {
        $this->getRepo()->loadCategorizationAttributes($this);
        return $this->attributes;
    }

    /**
     * @return bool
     */
    public function hasAttributesLoaded() {
        return $this->attributes !== null;
    }

    /**
     * @return string
     */
    public function getSaneName() {
        return GeneralUtilities::sanitizeFluidAccessName($this->name);
    }

    /**
     * @return string
     */
    public function getSaneType() {
        return GeneralUtilities::sanitizeFluidAccessName($this->type);
    }

    /* ArrayAccess implementation */
    public function offsetExists($offset)
    {
        $this->getAttributes();
        // 1-Based
        return array_key_exists($offset-1, $this->attributes);
    }

    public function offsetGet($offset)
    {
        $this->getAttributes();
        // 1-Based
        return $this->attributes[$offset-1];
    }

    public function offsetSet($offset, $value)
    {
        // not implemented
    }

    public function offsetUnset($offset)
    {
        // not implemented
    }
}
