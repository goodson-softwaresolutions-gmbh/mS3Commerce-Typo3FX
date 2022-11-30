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

    /**
     * Creates an empty attribute value
     * @param PimObject $object
     * @param Attribute $attribute
     * @return AttributeValue
     */
    public static function createEmptyFromObjectAndAttribute(PimObject $object, Attribute $attribute) {
        $ret = new AttributeValue(0);
        $ret->attribute = $attribute;
        $ret->featureId = $attribute->getId();
        $ret->languageId = $attribute->getLanguageId();
        $ret->productId = $object->isProduct() ? $object->getId() : 0;
        $ret->groupId = $object->isGroup() ? $object->getId() : 0;
        return $ret;
    }

    /**
     * @return int|null
     */
    public function getObjectId() {
        if ($this->groupId) return $this->groupId;
        if ($this->productId) return $this->productId;
        return null;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @return int
     */
    public function getFeatureId()
    {
        return $this->featureId;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * @return string
     */
    public function getContentHtml(): string
    {
        return $this->contentHtml;
    }

    /**
     * @return string
     */
    public function getContentPlain(): string
    {
        return $this->contentPlain;
    }

    /**
     * @return mixed
     */
    public function getContentNumber()
    {
        return $this->contentNumber;
    }

    /**
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->attribute;
    }

    public function __call($name, $arguments)
    {
        $ret = parent::__call($name, $arguments);
        if ($ret === null && $this->attribute != null) {
            return $this->attribute->__call($name, $arguments);
        }
        return $ret;
    }

    public function _map($row, $prefix) {
        $this->groupId = $row[$prefix.'GroupId'] ?? 0;
        $this->productId = $row[$prefix.'ProductId'] ?? 0;
        $this->featureId = $row[$prefix.'FeatureId'] ?? 0;
        $this->languageId = $row[$prefix.'LanguageId'] ?? 0;
        $this->contentHtml = $row[$prefix.'ContentHtml'] ?? '';
        $this->contentPlain = $row[$prefix.'ContentPlain'] ?? '';
        $this->contentNumber = $row[$prefix.'ContentNumber'] ?? null;
        return true;
    }
}
