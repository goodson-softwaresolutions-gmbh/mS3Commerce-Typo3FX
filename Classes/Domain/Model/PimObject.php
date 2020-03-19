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

use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;
use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PimObject
 * An object from mS3 PIM (Group / Product)
 * @package Ms3\Ms3CommerceFx\Domain\Model
 */
abstract class PimObject extends AbstractEntity
{
    const TypeNone = 0;
    const TypeGroup = 1;
    const TypeProduct = 2;
    // TODO: const TypeDocument = 3;

    /**
     * @var int
     */
    protected $menuId;
    protected $name;
    protected $auxiliaryName;
    protected $asimOid;
    protected $objectId;
    protected $structureElementId;

    /** @var PimObject[] */
    protected $children;
    /** @var AttributeValue[] */
    protected $attributes;
    /** @var Categorization[] */
    protected $categorizations;
    /** @var PimObjectCollection */
    protected $collection;

    public function __construct($id = 0) {
        parent::__construct($id);
    }

    public abstract function getEntityType() : int;

    /**
     * @return int
     */
    public function getMenuId()
    {
        return $this->menuId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAuxiliaryName()
    {
        return $this->auxiliaryName;
    }

    /**
     * @return string
     */
    public function getAsimOid()
    {
        return $this->asimOid;
    }

    /**
     * @return string
     */
    public function getGuid()
    {
        return $this->asimOid;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @return int
     */
    public function getStructureElementId()
    {
        return $this->structureElementId;
    }

    /**
     * @return PimObjectCollection
     */
    public function getCollection() {
        return $this->collection;
    }

    /**
     * @return PimObject
     */
    public function detached() {
        if ($this->collection == null) {
            return $this;
        }
        /** @var PimObject $copy */
        $copy = clone $this;
        $copy->collection = null;
        return $copy;
    }

    public function isGroup() : bool {
        return $this->getEntityType() == self::TypeGroup;
    }

    public function isProduct() : bool {
        return $this->getEntityType() == self::TypeProduct;
    }

    public function getIsGroup() : bool {
        return $this->isGroup();
    }

    public function getIsProduct() : bool {
        return $this->isProduct();
    }

    /**
     * @return PimObject[]
     */
    public function getChildren() {
        $this->getRepo()->loadObjectChildren($this);
        return $this->children;
    }

    /**
     * @return bool
     */
    public function childrenLoaded() : bool {
        return $this->children  !== null;
    }

    /**
     * @return PimObject|null
     */
    public function getFirstChild() {
        $this->getChildren();
        if ($this->children && count($this->children) > 0) {
            return $this->children[0]->detached();
        }
        return null;
    }

    /**
     * @return AttributeValue[]
     */
    public function getAttributes() {
        $this->getRepo()->loadObjectValues($this);
        return $this->attributes;
    }

    public function attributesLoaded() : bool {
        return $this->attributes !== null;
    }

    /**
     * @return StructureElement
     */
    public function getStructureElement() {
        return $this->getRepo()->getStructureElementById($this->structureElementId);
    }

    /**
     * @return CategorizationProxy[]
     */
    public function getCategorization() {
        $this->getRepo()->loadObjectCategorizations($this);
        return $this->valuedCategorizations;
    }

    public function categorizationsLoaded() : bool {
        return $this->categorizations !== null;
    }

    /**
     * @param Categorization[] $categorizations
     */
    public function setCategorizations($categorizations) {
        // Make map from category name to category
        $this->categorizations = GeneralUtilities::toDictionary($categorizations, function($c) { return $c->getSaneType(); });
        $this->assignCategorizationValues();
    }

    private $valuedCategorizations;
    private function assignCategorizationValues() {
        $this->valuedCategorizations = [];
        foreach ($this->categorizations as $cat) {
            $this->valuedCategorizations[$cat->getSaneType()] = new CategorizationProxy($this, $cat);
        }
    }
}

class CategorizationProxy implements \ArrayAccess, \Iterator
{
    /** @var PimObject */
    private $obj;
    /** @var Categorization */
    private $categorization;
    private $pos;

    /** @var AttributeValue */
    private $filledAttributes;

    /**
     * CategorizationProxy constructor.
     * @param PimObject $obj
     * @param Categorization $categorization
     */
    public function __construct($obj, $categorization)
    {
        $this->obj = $obj;
        $this->categorization = $categorization;
        $pos = 0;
    }

    /**
     * @return Categorization
     */
    public function getCategorization() {
        return $this->categorization;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes() {
        return $this->categorization->getAttributes();
    }

    /* ArrayAccess implementation: Access by position in categorization */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->categorization->getAttributes());
    }

    public function offsetGet($offset)
    {
        $attr = $this->categorization->getAttributes()[$offset];
        // If object has no value, this will be null. Create dummy value
        $va = $this->obj->getAttributes()[$attr->getSaneName()];
        if ($va == null) {
            $va = AttributeValue::createEmptyFromObjectAndAttribute($this->obj, $attr);
        }
        return $va;
    }

    public function offsetSet($offset, $value)
    {
        // not implemented
    }

    public function offsetUnset($offset)
    {
        // not implemented
    }

    /* Iterator implementation: Access only attributes with values */
    public function current()
    {
        return $this->getFilledAttributes()[$this->pos];
    }

    public function next()
    {
        $this->pos++;
    }

    public function key()
    {
        return $this->pos;
    }

    public function valid()
    {
        return $this->pos < count($this->getFilledAttributes());
    }

    public function rewind()
    {
        $this->pos = 0;
    }

    private function getFilledAttributes() {
        if ($this->filledAttributes == null) {
            $this->filledAttributes = [];
            foreach ($this->categorization->getAttributes() as $attr) {
                $fa = $this->obj->getAttributes()[$attr->getSaneName()];
                if ($fa) {
                    $this->filledAttributes[] = $fa;
                }
            }
        }
        return $this->filledAttributes;
    }
}
