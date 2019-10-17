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

use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Model\PimObjectCollection;

class PimObjectCollectionRepository extends PimObjectRepository
{
    /**
     * @param PimObjectCollection $coll
     */
    public function loadAttributeValues($coll)
    {
        $groups = $coll->getOfType(PimObject::TypeGroup);
        $prods = $coll->getOfType(PimObject::TypeProduct);

        $groups = array_filter($groups, function($g) { return !$g->attributesLoaded(); } );
        $prods = array_filter($prods, function($p) { return !$p->attributesLoaded(); } );

        $groupAttrs = $this->loadAttributesByObjects(array_map(function($g) { return $g->getId(); }, $groups), PimObject::TypeGroup);
        $prodAttrs = $this->loadAttributesByObjects(array_map(function($p) { return $p->getId(); }, $prods), PimObject::TypeProduct);

        foreach ($groups as $g) {
            $g->_setProperty('attributes', $groupAttrs[$g->getId()]);
        }

        foreach ($prods as $p) {
            $p->_setProperty('attributes', $prodAttrs[$p->getId()]);
        }
    }

    /**
     * @param PimObjectCollection $coll
     */
    public function loadChildren($coll)
    {
        $objects = $coll->all();
        $objects = array_filter($objects, function($o) { return !$o->childrenLoaded() && $o->getMenuId(); });

        if (empty($objects)) {
            return;
        }

        $menuIds = array_map(function($o) { return $o->getMenuId(); }, $objects);
        $menuMap = $this->loadMenuBy($this->_q()->expr()->in('m.ParentId', $menuIds));
        foreach ($objects as $o) {
            if (array_key_exists($o->getMenuId(), $menuMap)) {
                $o->_setProperty('children', $menuMap[$o->getMenuId()]);
            } else {
                $o->_setProperty('children', []);
            }
        }
    }
}
