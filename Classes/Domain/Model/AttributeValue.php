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

class AttributeValue extends AbstractEntity
{
    public function __construct($id) {
        parent::__construct($id);
    }

    protected $groupId;
    protected $productId;
    protected $featureId;
    protected $languageId;
    /** @var string */
    protected $contentHtml;
    /** @var string */
    protected $contentPlain;
    protected $contentNumber;

    /** @var Attribute */
    protected $attribute;

    public function __toString() {
        return $this->contentPlain;
    }

    public function getObjectId() {
        if ($this->groupId) return $this->groupId;
        if ($this->productId) return $this->productId;
        return null;
    }

    public function __call($name, $arguments)
    {
        $ret = parent::__call($name, $arguments);
        if ($ret === null && $this->attribute != null) {
            return $this->attribute->__call($name, $arguments);
        }
        return null;
    }
}
