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

namespace Ms3\Ms3CommerceFx\Domain\Repository;

use Ms3\Ms3CommerceFx\Domain\Model\StructureElement;

class StructureElementRepository extends RepositoryBase
{
    /**
     * @var StructureElement[]
     */
    private $allStructures = null;

    /**
     * Gets a structure element by Id
     * @param int $structureElementId The structure element's id
     * @return StructureElement
     */
    public function getStructureElementById($structureElementId) {
        $this->loadAll();
        return $this->allStructures[$structureElementId];
    }

    /**
     * Gets all Structure Elements
     * @return StructureElement[] Mapping from Id to StructureElement
     */
    public function getAll() {
        $this->loadAll();
        return $this->allStructures;
    }

    /**
     * Returns the product level (lowest level in hierarchy)
     * Note: Set Composition is not considered here.
     * @return StructureElement|null
     */
    public function getProductLevel() {
        $this->loadAll();
        foreach ($this->allStructures as $structure) {
            if ($structure->getOrderNr() == 1) {
                return $structure;
            }
        }
        return null;
    }

    /**
     * Gets a structure element by its name.
     * @param string $name The name
     * @return StructureElement|null
     */
    public function getStructureElementByName($name) {
        $this->loadAll();
        foreach ($this->allStructures as $structure) {
            if ($structure->getName() == $name) {
                return $structure;
            }
        }
        return null;
    }

    private function loadAll() {
        if ($this->allStructures != null) {
            return;
        }

        $this->allStructures = [];

        $q = $this->_q();
        $res = $q
            ->select('*')
            ->from('StructureElement')
            ->orderBy('OrderNr')
            ->execute();

        while ($row = $res->fetch()) {
            $str = new StructureElement($row['Id']);
            $this->mapper->mapObject($str, $row);
            $this->allStructures[$str->getId()] = $str;
            $this->store->registerObject($str);
        }
    }
}
