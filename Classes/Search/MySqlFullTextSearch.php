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

namespace Ms3\Ms3CommerceFx\Search;

use Ms3\Ms3CommerceFx\Domain\Repository\RepositoryBase;
use Ms3\Ms3CommerceFx\Persistence\QuerySettings;
use TYPO3\CMS\Core\SingletonInterface;

class MySqlFullTextSearch extends RepositoryBase implements FullTextSearchInterface, SingletonInterface
{
    public function insertFullTextMatches($tableName, $shopId, $term, $rootMenuId)
    {
        $terms = $this->processTerm($term);
        $match = $this->getMatchQuery($terms, $shopId);
        // Currently only product search
        $match->andWhere('ParentType = 2');

        $q = $this->_q();
        $q->select('m.Id, m.Path,  -(ft.score1+ft.score2+ft.score3) AS Sort')
            ->from('Product', 'p')
            ->innerJoin('p', "({$match->getSQL()})", 'ft', $q->expr()->andX(
                $q->expr()->eq('p.Id', 'ft.ParentId'),
                $q->expr()->eq('ft.ParentType', 2)
                ))
            ->innerJoin('p', 'Menu', 'm', $q->expr()->eq('m.ProductId', 'p.Id'))
            ;

        // TODO: restrict to rootMenuId

        $cols = ['MenuId', 'Path', 'Sort'];
        $cols = array_merge($cols, SearchQueryUtils::addObjectKeyToQuery($q));
        $cols = array_merge($cols, SearchQueryUtils::addRestrictionValuesToQuery($this->querySettings, $q));

        $q->setParameter(':term', $terms);
        SearchQueryUtils::executeInsert($this->db, $q, $tableName, $cols);
    }

    private function getMatchQuery($terms, $shopId)
    {
        $ftTable = 'FullText_'.$shopId;

               // Find matching entries and score
        $q = $this->_q();
        $scoreFields = [];
        $searchFields = [];
        for ($i = 0; $i <= 3; ++$i) {
            $n = $i==0?'Display':'SearchTerms'.$i;
            $searchFields[] = $n;
            $f = /** @lang MySQL */"(0+LEAST((MATCH($n) AGAINST (:term IN BOOLEAN MODE)), 100000)*POW(2,4-$i)) AS score$i";
            $scoreFields[] = $f;
        }

        $q->select('ParentId, ParentType, '. implode(',', $scoreFields))
            ->from($ftTable)
            ->where('MATCH (' . implode(',', $searchFields) .") AGAINST (:term IN BOOLEAN MODE)");

        return $q;
    }

    private function processTerm($term) {
        // Build boolean terms => append '*' to each single word. TODO: Add +? ("And" query)
        $terms = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
        $termsBool = array_map(function($t) { return strtolower($t).'*'; }, $terms);
        return implode(' ', $termsBool);
    }
}
