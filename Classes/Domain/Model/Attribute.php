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

    public function __construct($id) {
        parent::__construct($id);
    }

    public function getSaneName() {
        return preg_replace('/\W/', '_', $this->name);
    }

    public function getStructureElement() {
        return $this->getRepo()->getStructureElementById($this->structureElementId);
    }
}
