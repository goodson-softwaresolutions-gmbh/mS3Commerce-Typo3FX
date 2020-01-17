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


class SearchRepository extends RepositoryBase
{
    public function findInPath($path, $structureElement = '') {
        $q = $this->_q();
        $q->select('m.Id')
            ->from('Menu', 'm');
        $q->andWhere($q->expr()->like('m.Path', $path.'%'));

        if ($structureElement != '') {
            $q->innerJoin('m', 'StructureElement', 's', 's.Id = m.StructureElementId');
            $q->andWhere($q->expr()->eq('s.Name', $q->createNamedParameter($structureElement)));
        }

        $res = $q->execute()->fetchAll();
        return array_map(function($x) { return $x['Id']; }, $res);
    }

    public function findInMenuId($menuId, $structureElement = '') {
        $q = $this->_q();
        $q->select('m.Id')
            ->from('Menu', 'm');
        $q->innerJoin('m', 'Menu', 'pm',
            $q->expr()->like('m.Path', "CONCAT(pm.Path, '%')"));
        $q->andWhere($q->expr()->eq('pm.Id', $menuId));

        if ($structureElement != '') {
            $q->innerJoin('m', 'StructureElement', 's', 's.Id = m.StructureElementId');
            $q->andWhere($q->expr()->eq('s.Name', $q->createNamedParameter($structureElement)));
        }
        $res = $q->execute()->fetchAll();
        return array_map(function($x) { return $x['Id']; }, $res);
    }

    public function findStructureElements($structureElement) {
        $q = $this->_q();
        $q->select('m.Id')
            ->from('Menu', 'm');

        $q->innerJoin('m', 'StructureElement', 's', 's.Id = m.StructureElementId');
        $q->andWhere($q->expr()->eq('s.Name', $q->createNamedParameter($structureElement)));

        $res = $q->execute()->fetchAll();
        return array_map(function($x) { return $x['Id']; }, $res);
    }
}
