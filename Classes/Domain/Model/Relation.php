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

class Relation extends AbstractEntity
{
    /** @var string */
    protected $name;
    /** @var string */
    protected $title;
    /** @var bool */
    protected $isMother;
    /** @var int {@see PimObject::TypeGroup} or {@see PimObject::TypeProduct} */
    protected $destinationType;
    /** @var int */
    protected $destinationId;
    /** @var string */
    protected $text1;
    /** @var bool */
    protected $printText1;
    /** @var string */
    protected $text2;
    /** @var bool */
    protected $printText2;
    /** @var int */
    protected $amount;
    /** @var int */
    protected $orderNr;

    /** @var PimObject */
    protected $parent;
    /** @var PimObject */
    protected $child;

    public function __construct(int $id, PimObject $parent)
    {
        parent::__construct($id);
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return bool
     */
    public function isMother(): bool
    {
        return $this->isMother;
    }

    /**
     * @return int
     */
    public function getDestinationType(): int
    {
        return $this->destinationType;
    }

    /**
     * @return int
     */
    public function getDestinationId(): int
    {
        return $this->destinationId;
    }

    /**
     * @return string
     */
    public function getText1(): string
    {
        return $this->text1;
    }

    /**
     * @return bool
     */
    public function isPrintText1(): bool
    {
        return $this->printText1;
    }

    /**
     * @return string
     */
    public function getText2(): string
    {
        return $this->text2;
    }

    /**
     * @return bool
     */
    public function isPrintText2(): bool
    {
        return $this->printText2;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return PimObject
     */
    public function getParent(): PimObject
    {
        return $this->parent;
    }

    /**
     * @return PimObject
     */
    public function getChild()
    {
        if (!$this->child)
            $this->parent->loadRelationChildren($this->name);
        return $this->child;
    }
}
