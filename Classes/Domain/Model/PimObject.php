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

    public function getChildren() {
        $this->getRepo()->loadObjectChildren($this);
        return $this->children;
    }

    public function childrenLoaded() {
        return $this->children  !== null;
    }

    public function getAttributes() {
        $this->getRepo()->loadObjectValues($this);
        return $this->attributes;
    }

    public function attributesLoaded() {
        return $this->attributes !== null;
    }

    public function getStructureElement() {
        return $this->getRepo()->getStructureElementById($this->structureElementId);
    }

    public function getCategorization() {
        $this->getRepo()->loadObjectCategorizations($this);
        return $this->valuedCategorizations;
    }

    public function categorizationsLoaded() {
        return $this->categorizations !== null;
    }

    /**
     * @param Categorization[] $categorizations
     */
    public function setCategorizations($categorizations) {
        $this->categorizations = $categorizations;
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

class CategorizationProxy implements \ArrayAccess
{
    /** @var PimObject */
    private $obj;
    /** @var Categorization */
    private $categorization;

    /**
     * CategorizationProxy constructor.
     * @param PimObject $obj
     * @param Categorization $categorization
     */
    public function __construct($obj, $categorization)
    {
        $this->obj = $obj;
        $this->categorization = $categorization;
    }

    public function getCategorization() {
        return $this->categorization;
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->categorization->getAttributes());
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        $attr = $this->categorization->getAttributes()[$offset];
        return $this->obj->getAttributes()[$attr->getSaneName()];
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }
}
