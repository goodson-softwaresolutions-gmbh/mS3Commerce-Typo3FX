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
use Ms3\Ms3CommerceFx\Domain\Model\Relation;
use Ms3\Ms3CommerceFx\Service\GeneralUtilities;
use Ms3\Ms3CommerceFx\Service\ObjectHelper;

class RelationRepository extends RepositoryBase
{
    /** @var PimObjectRepository */
    private $objRepo;
    /**
     * @param PimObjectRepository $repo
     */
    public function injectObjectRepository(PimObjectRepository $repo) {
        $this->objRepo = $repo;
    }

    /**
     * @param PimObject[] $objects
     */
    public function loadRelations($objects) {
        $objects = array_filter($objects, function($o) { return !$o->relationsLoaded(); });
        if (empty($objects)) {
            return;
        }

        $groups = array_filter($objects, function($o) { return $o->isGroup(); });
        $products = array_filter($objects, function($o) { return $o->isProduct(); });

        $groupIds = ObjectHelper::getIdsFromObjects($groups);
        $productIds = ObjectHelper::getIdsFromObjects($products);

        $groups = GeneralUtilities::toDictionary($groups, [ObjectHelper::class, 'getKeyFromObject']);
        $products = GeneralUtilities::toDictionary($products, [ObjectHelper::class, 'getKeyFromObject']);

        $q = $this->_q();
        $q->select('*')
            ->from('Relations');

        if (!empty($productIds))
            $q->orWhere($q->expr()->in('ProductId', $productIds));
        if (!empty($groupIds))
            $q->orWhere($q->expr()->in('GroupId', $groupIds));

        $res = $q->execute();
        $map = [];
        while ($row = $res->fetchAssociative()) {
            if ($row['GroupId']) {
                $key = ObjectHelper::buildKeyForObject($row['GroupId'], PimObject::TypeGroup);
                $parent = $groups[$key];
            } else if ($row['ProductId']) {
                $key = ObjectHelper::buildKeyForObject($row['ProductId'], PimObject::TypeProduct);
                $parent = $products[$key];
            } else {
                continue;
            }

            $rel = new Relation($row['Id'], $parent);
            $this->mapper->mapObject($rel, $row);
            if (!array_key_exists($key, $map)) $map[$key] = [];
            $map[$key][] = $rel;
        }

        // Must materialize now, as destination might be not visible
        $rrr = GeneralUtilities::flattenArray($map);
        $this->materializeRelations($rrr);

        function assignRelations($map, $objects) {
            foreach ($objects as $k => $o) {
                $rels = [];
                if (array_key_exists($k, $map)) {
                    $rels = $map[$k];
                    // Remove invisible relations
                    $rels = array_filter($rels, function($r) { return $r->_getProperty('child');} );
                    $rels = GeneralUtilities::groupBy($rels, function($x) { return GeneralUtilities::sanitizeFluidAccessName($x->getName());});
                    foreach ($rels as &$items) {
                        usort($items, function($a, $b) { return $a->_getProperty('orderNr') <=> $b->_getProperty('orderNr');} );
                    }
                }
                $o->_setProperty('relations', $rels);
            }
        }

        assignRelations($map, $groups);
        assignRelations($map, $products);
    }

    /**
     * @param PimObject[] $objects
     * @param string $relationType
     */
    public function loadRelationChildren($objects, $relationType)
    {
        /** @var Relation[] $loadRelations */
        $loadRelations = [];
        foreach ($objects as $o) {
            $rels = $o->getRelations();
            if ($relationType != null) {
                $rels = $rels[$relationType];
            } else {
                $rels = GeneralUtilities::flattenArray($rels);
            }
            $loadRelations = array_merge($loadRelations, $rels);
        }

        $this->materializeRelations($loadRelations);
    }

    private function materializeRelations($loadRelations)
    {
        $relations = GeneralUtilities::groupBy($loadRelations, function($x) { return $x->getDestinationType(); }, function($x) { return $x->getDestinationId(); });
        $groups = $this->objRepo->getObjectsByIds(PimObject::TypeGroup, $relations[PimObject::TypeGroup]);
        $products = $this->objRepo->getObjectsByIds(PimObject::TypeProduct, $relations[PimObject::TypeProduct]);

        foreach ($loadRelations as $r) {
            if ($r->getDestinationType() == PimObject::TypeGroup) {
                $r->_setProperty('child', $groups[$r->getDestinationId()]);
            }
            if ($r->getDestinationType() == PimObject::TypeProduct) {
                $r->_setProperty('child', $products[$r->getDestinationId()]);
            }
        }
    }
}
