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

namespace Ms3\Ms3CommerceFx\Service;

use Ms3\Ms3CommerceFx\Domain\Model\Attribute;
use Ms3\Ms3CommerceFx\Domain\Model\Group;
use Ms3\Ms3CommerceFx\Domain\Model\Product;
use TYPO3\CMS\Core\SingletonInterface;

class ObjectCreationService implements SingletonInterface
{
    public function createProduct($id) {
        return new Product($id);
    }

    public function createGroup($id) {
        return new Group($id);
    }

}
