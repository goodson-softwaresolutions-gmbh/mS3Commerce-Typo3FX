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

use Ms3\Ms3CommerceFx\Domain\Model\Menu;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;

class RepositoryFacade implements \TYPO3\CMS\Core\SingletonInterface
{
    /** @var PimObjectRepository */
    private $object;
    public function injectObject(PimObjectRepository $or) {
        $this->object = $or;
    }

    /** @var PimObjectCollectionRepository */
    private $objectCollection;
    public function injectObjectCollection(PimObjectCollectionRepository $ocr) {
        $this->objectCollection = $ocr;
    }

    /**
     * @param int $menuId
     * @return Menu
     */
    public function getObjectByMenuId($menuId) {
        return $this->object->getByMenuId($menuId);
    }

    /**
     * @param $object PimObject
     */
    public function loadObjectValues($object) {
        if ($object->getCollection()) {
            $this->objectCollection->loadAttributeValues($object->getCollection());
        } else {
            $this->object->loadAttributeValues($object);
        }
    }

    /**
     * @param $object PimObject
     */
    public function loadObjectChildren($object) {
        if ($object->getCollection()) {
            $this->objectCollection->loadChildren($object->getCollection());
        } else {
            $this->object->loadChildren($object);
        }
    }
}
