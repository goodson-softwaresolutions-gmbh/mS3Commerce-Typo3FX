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
 * Class Menu
 * An PimObject in the hierarchy. The object can be accessed by the $object property
 * @package Ms3\Ms3CommerceFx\Domain\Model
 */
class Menu extends AbstractEntity
{
    public function __construct(int $menuId)
    {
        parent::__construct($menuId);
    }

    public function getObjectEntityType() {
        if ($this->object != null) {
            return $this->object->getEntityType();
        }

        if ($this->groupId) {
            return PimObject::TypeGroup;
        }

        if ($this->productId) {
            return PimObject::TypeProduct;
        }

        return PimObject::TypeNone;
    }

    protected $languageId;
    protected $marketId;
    protected $parentId;
    protected $depth;
    protected $ordinal;
    protected $path;
    protected $contextID;
    protected $groupId;
    protected $productId;
    protected $documentId;

    /**
     * @var PimObject
     */
    protected $object;

    public function setObject(PimObject $obj) {
        $this->object = $obj;
    }
}
