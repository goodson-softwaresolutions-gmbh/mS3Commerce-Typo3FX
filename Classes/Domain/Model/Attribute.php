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

class Attribute extends AbstractEntity
{
    protected $asimOid;
    /** @var string */
    protected $name;
    protected $languageId;
    protected $marketId;
    protected $userRights; // REMOVE?

    protected $structureElementId;
    protected $auxiliaryName;
    protected $title;
    protected $info;
    protected $unitToken;
    protected $dimension;
    protected $prefix;
    protected $type;
    protected $version;
    protected $tableData;

    private $_saneName;
    private $_saneAuxName;

    public function __construct($id) {
        parent::__construct($id);
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * @return int
     */
    public function getMarketId()
    {
        return $this->marketId;
    }

    /**
     * @return string
     */
    public function getUserRights()
    {
        return $this->userRights;
    }

    /**
     * @return int
     */
    public function getStructureElementId()
    {
        return $this->structureElementId;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return string
     */
    public function getUnitToken()
    {
        return $this->unitToken;
    }

    /**
     * @return string
     */
    public function getDimension()
    {
        return $this->dimension;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getTableData()
    {
        return $this->tableData;
    }

    /**
     * @return string
     */
    public function getSaneName() {
        if (empty($this->_saneName)) {
            $this->_saneName = GeneralUtilities::sanitizeFluidAccessName($this->name);
        }
        return $this->_saneName;
    }

    /**
     * @return string
     */
    public function getSaneAuxiliaryName() {
        if (empty($this->_saneAuxName)) {
            $this->_saneAuxName = GeneralUtilities::sanitizeFluidAccessName($this->auxiliaryName);
        }
        return $this->_saneAuxName;
    }

    /**
     * @return StructureElement
     */
    public function getStructureElement() {
        return $this->getRepo()->getStructureElementById($this->structureElementId);
    }
}
