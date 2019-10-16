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

    /**
     * @var PimObject[]
     */
    protected $children;

    /**
     * @var PimObjectCollection
     */
    protected $collection;

    public function __construct($id = 0) {
        parent::__construct($id);
    }

    /**
     * @var AttributeValue[]
     */
    protected $attributes;

    public abstract function getEntityType() : int;

    public function isGroup() : bool {
        return $this->getEntityType() == self::TypeGroup;
    }

    public function isProduct() : bool {
        return $this->getEntityType() == self::TypeProduct;
    }

    public function getChildren() {
        if ($this->children === null && $this->menuId) {
            /** @var PimObjectRepository $repo */
            $repo = GeneralUtility::makeInstance(PimObjectRepository::class);
            $this->children = $repo->getChildren($this->menuId);
        }
        return $this->children;
    }

    public function getAttributes() {
        if ($this->collection) {
            /** @var PimObjectRepository $repo */
            $repo = GeneralUtility::makeInstance(PimObjectRepository::class);
            $repo->loadAttributeValuesCollection($this->collection);
        } else if (!$this->hasAttributes()) {
            /** @var PimObjectRepository $repo */
            $repo = GeneralUtility::makeInstance(PimObjectRepository::class);
            $repo->loadAttributeValues($this);
        }
        return $this->attributes;
    }

    public function hasAttributes() {
        return $this->attributes != null;
    }
}
